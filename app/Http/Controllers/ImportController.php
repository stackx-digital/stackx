<?php

namespace App\Http\Controllers;

use App\Services\Ingest\AdMetricsImporter;
use App\Services\Ingest\DefaultAdAccount;
use App\Services\Ingest\MetaCsvParser;
use App\Services\Ingest\MetaHeaderMap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CSV ingest (§5). Two steps: preview (parse + show honest mapping) → confirm
 * (upsert). The MVP path — the Meta API sync stays behind a feature flag.
 */
class ImportController extends Controller
{
    public function __construct(
        private readonly MetaCsvParser $parser,
        private readonly AdMetricsImporter $importer,
        private readonly DefaultAdAccount $defaultAccount,
    ) {}

    public function show(): Response
    {
        return Inertia::render('Analytics/Import');
    }

    /** Parse the upload and show a mapping preview before committing. */
    public function preview(Request $request): Response
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10 MB
        ]);

        $token = Str::uuid()->toString();
        $path = "imports/{$token}.csv";
        Storage::disk('local')->put($path, $request->file('file')->get());

        $parsed = $this->parser->parseFile(Storage::disk('local')->path($path));

        return Inertia::render('Analytics/Import', [
            'importToken' => $token,
            'preview' => [
                'fileName' => $request->file('file')->getClientOriginalName(),
                'rowCount' => $parsed->rowCount(),
                'mapping' => $parsed->mapping,               // canonical => original header
                'recognized' => $parsed->recognizedFields(),
                'unknownHeaders' => $parsed->unknownHeaders,
                'missingImportant' => $parsed->missingImportantFields(),
                'metricFields' => MetaHeaderMap::METRIC_FIELDS,
                'sampleRows' => array_slice($parsed->rows, 0, 5),
            ],
        ]);
    }

    /** Commit a previously previewed upload. */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'import_token' => ['required', 'string'],
        ]);

        $path = 'imports/'.basename($validated['import_token']).'.csv';

        if (! Storage::disk('local')->exists($path)) {
            return redirect()->route('import.show')
                ->with('status', 'That upload expired — please choose the file again.');
        }

        $parsed = $this->parser->parseFile(Storage::disk('local')->path($path));
        $account = $this->defaultAccount->resolve();
        $result = $this->importer->import($account, $parsed);

        Storage::disk('local')->delete($path);

        return redirect()->route('analytics')->with('status', sprintf(
            'Imported %d rows — %d ads created, %d updated, %d daily metrics written.',
            $parsed->rowCount(),
            $result->adsCreated,
            $result->adsMatched,
            $result->metricsCreated + $result->metricsUpdated,
        ));
    }
}
