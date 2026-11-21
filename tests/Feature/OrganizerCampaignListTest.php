<?php

namespace Tests\Feature;

use App\Filament\Organizer\Resources\Raffles\RaffleResource;
use App\Models\Organizer;
use App\Models\Raffle;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Livewire\Livewire;
use App\Filament\Organizer\Resources\Raffles\Pages\ListRaffles;

class OrganizerCampaignListTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_only_shows_campaigns_owned_by_the_selected_organizer(): void
    {
        $habilis = $this->organizer('Rifa Habilis', 'rifa-habilis');
        $other = $this->organizer('Outro Organizador', 'outro-organizador');

        $mine = $this->raffle('Campanha Habilis', 'habilis-teste', $habilis->id);
        $foreign = $this->raffle('Campanha Estranha', 'outro-teste', $other->id);
        $legacy = $this->raffle('Campanha Legada', 'legado-teste', null);

        $master = User::factory()->create(['role' => null]);
        $master->organizers()->attach($habilis->id, ['role' => 'master']);

        $this->be($master);

        Filament::setCurrentPanel('organizer');
        Filament::setTenant($habilis);
        Filament::bootCurrentPanel();

        $visibleIds = RaffleResource::getEloquentQuery()->pluck('id')->all();

        $this->assertSame([$mine->id], $visibleIds);
        $this->assertNotContains($foreign->id, $visibleIds);
        $this->assertNotContains($legacy->id, $visibleIds);
        $this->assertSame([$mine->id], $habilis->raffles()->pluck('id')->all());

        $this->assertFalse(RaffleResource::canCreate());
        $this->assertFalse(RaffleResource::canEdit($mine));
        $this->assertFalse(RaffleResource::canDelete($mine));
        $this->assertFalse(RaffleResource::canView($mine));
    }

    public function test_master_can_open_only_their_organizers_campaign_page(): void
    {
        $own = $this->organizer('Organizador Alfa', 'org-alfa');
        $other = $this->organizer('Organizador Beta', 'org-beta');
        $mine = $this->raffle('Sorteio de Alfa', 'sorteio-alfa', $own->id);
        $foreign = $this->raffle('Sorteio de Beta', 'sorteio-beta', $other->id);
        $legacy = $this->raffle('Sorteio sem organizador', 'sorteio-legado', null);

        $master = User::factory()->create(['role' => null, 'active' => true]);
        $master->organizers()->attach($own->id, ['role' => 'master']);
        $master->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
        $this->actingAs($master);

        $this->get('/organizador/org-alfa/campanhas')
            ->assertOk()
            ->assertSeeText($mine->title)
            ->assertDontSeeText($foreign->title)
            ->assertDontSeeText($legacy->title);

        $response = $this->get('/organizador/org-beta/campanhas');
        $this->assertContains($response->getStatusCode(), [403, 404]);
        $response->assertDontSeeText($foreign->title);
    }

    public function test_organizer_campaign_list_route_is_registered_separately(): void
    {
        $route = Route::getRoutes()
            ->getByName('filament.organizer.resources.campanhas.index');

        $this->assertNotNull($route);
        $this->assertSame('organizador/{tenant}/campanhas', $route->uri());

        $this->assertNull(
            Route::getRoutes()->getByName('filament.organizer.resources.campanhas.create')
        );
    }

    public function test_only_a_master_of_the_current_organizer_can_create_a_draft(): void
    {
        $own = $this->organizer('Organizador Alfa', 'org-alfa');
        $other = $this->organizer('Organizador Beta', 'org-beta');
        $master = User::factory()->create(['role' => null, 'active' => true]);
        $master->organizers()->attach($own, ['role' => 'master']);
        $master->organizers()->attach($other, ['role' => 'manager']);
        $this->actingAs($master);
        Filament::setCurrentPanel('organizer');
        Filament::setTenant($own);
        Filament::bootCurrentPanel();

        Livewire::test(ListRaffles::class)
            ->callAction('createDraft', data: [
                'title' => 'Primeiro rascunho',
                'total_numbers' => 250,
                'ticket_price' => '2,50',
            ])->assertHasNoActionErrors();

        $this->assertDatabaseHas('raffles', [
            'title' => 'Primeiro rascunho', 'organizer_id' => $own->id,
            'status' => 'draft', 'total_numbers' => 250, 'ticket_price_cents' => 250,
        ]);
        Filament::setTenant($other);
        Livewire::test(ListRaffles::class)->assertActionHidden('createDraft');
        $this->assertDatabaseCount('raffles', 1);
    }

    private function organizer(string $name, string $slug): Organizer
    {
        return Organizer::query()->create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
        ]);
    }

    private function raffle(string $name, string $slug, ?int $organizerId): Raffle
    {
        return Raffle::query()->create([
            'organizer_id' => $organizerId,
            'title' => $name,
            'slug' => $slug,
            'status' => 'draft',
            'ticket_price' => '1,00',
            'total_numbers' => 100,
        ]);
    }
}
