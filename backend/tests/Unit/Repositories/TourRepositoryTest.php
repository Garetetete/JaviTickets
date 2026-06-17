<?php

namespace Tests\Unit\Repositories;

use App\Models\Tour;
use App\Repositories\Eloquent\EloquentTourRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TourRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentTourRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new EloquentTourRepository;
    }

    public function test_create_and_find_by_slug(): void
    {
        $tour = $this->repo->create([
            'slug' => 'demo-tour',
            'name' => 'Demo Tour',
            'artist_name' => 'Artista Demo',
        ]);

        $this->assertDatabaseHas('tours', ['slug' => 'demo-tour']);
        $this->assertEquals($tour->id, $this->repo->findBySlug('demo-tour')->id);
        $this->assertNull($this->repo->findBySlug('no-existe'));
    }

    public function test_all_active_excludes_inactive(): void
    {
        $this->repo->create(['slug' => 'a', 'name' => 'A', 'artist_name' => 'X', 'is_active' => true]);
        $this->repo->create(['slug' => 'b', 'name' => 'B', 'artist_name' => 'X', 'is_active' => false]);

        $this->assertCount(1, $this->repo->allActive());
    }

    public function test_soft_delete_and_restore(): void
    {
        $tour = $this->repo->create(['slug' => 'sd', 'name' => 'SD', 'artist_name' => 'X']);

        $this->repo->delete($tour->id);
        $this->assertSoftDeleted('tours', ['id' => $tour->id]);
        $this->assertNull($this->repo->find($tour->id));

        $this->repo->restore($tour->id);
        $this->assertNotNull($this->repo->find($tour->id));
        $this->assertNull(Tour::find($tour->id)->deleted_at);
    }
}
