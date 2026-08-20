<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardItem;
use App\Models\Competitor;
use App\Models\CompetitorAd;
use App\Models\Organization;
use App\Models\User;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('stackx.allowed_emails', ['@stackx.my']);
    }

    private function bootData(): array
    {
        $org = Organization::firstOrCreate(['slug' => 'stackx'], ['name' => 'STACKx']);
        app(CurrentOrganization::class)->set($org->id);
        $user = User::factory()->create(['email' => 'buyer@stackx.my']);

        return [$org, $user];
    }

    public function test_create_board_and_add_external_item(): void
    {
        [$org, $user] = $this->bootData();

        $this->actingAs($user)->post('/library', ['name' => 'Raya winners'])
            ->assertRedirect();
        $board = Board::firstOrFail();
        $this->assertSame($org->id, $board->organization_id);

        $this->actingAs($user)->post("/library/{$board->id}/items", [
            'source' => 'external',
            'title' => 'Great hook',
            'body' => 'Jimat besar Raya ni!',
            'note' => 'strong opener',
        ])->assertRedirect(route('boards.show', $board));

        $this->assertDatabaseHas('board_items', [
            'board_id' => $board->id,
            'source' => 'external',
            'title' => 'Great hook',
        ]);
    }

    public function test_add_competitor_ad_pulls_its_body(): void
    {
        [$org, $user] = $this->bootData();
        $board = Board::create(['organization_id' => $org->id, 'name' => 'Swipe']);
        $competitor = Competitor::create(['organization_id' => $org->id, 'name' => 'Rival']);
        $ad = CompetitorAd::create(['competitor_id' => $competitor->id, 'ad_library_id' => 'x1', 'body' => 'Mega sale today']);

        $this->actingAs($user)->post("/library/{$board->id}/items", [
            'source' => 'competitor_ad',
            'source_id' => $ad->id,
        ])->assertRedirect();

        $item = BoardItem::firstOrFail();
        $this->assertSame('competitor_ad', $item->source);
        $this->assertSame('Mega sale today', $item->body);
        $this->assertSame('Rival', $item->title);
    }

    public function test_remove_item_and_delete_board(): void
    {
        [$org, $user] = $this->bootData();
        $board = Board::create(['organization_id' => $org->id, 'name' => 'Temp']);
        $item = $board->items()->create(['organization_id' => $org->id, 'source' => 'external', 'title' => 'x']);

        $this->actingAs($user)->delete("/library/items/{$item->id}")->assertRedirect();
        $this->assertDatabaseMissing('board_items', ['id' => $item->id]);

        $this->actingAs($user)->delete("/library/{$board->id}")->assertRedirect(route('library'));
        $this->assertDatabaseMissing('boards', ['id' => $board->id]);
    }

    public function test_index_renders(): void
    {
        [, $user] = $this->bootData();

        $this->actingAs($user)->get('/library')
            ->assertInertia(fn ($page) => $page->component('Library/Index')->has('boards'));
    }
}
