<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public, read-only report view (§5, P5). No auth — access is by the random
 * token only. A missing/revoked token 404s.
 */
class PublicReportController extends Controller
{
    public function show(string $token): Response
    {
        $report = Report::findByToken($token);

        if (! $report) {
            throw new NotFoundHttpException('Report not found or revoked.');
        }

        return Inertia::render('Reports/Public', [
            'title' => $report->title,
            'payload' => $report->payload,
        ]);
    }
}
