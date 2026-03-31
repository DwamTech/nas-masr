<?php

namespace Tests\Feature\Admin;

use App\Models\BackupHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Setup storage
        Storage::fake('local');
    }

    /** @test */
    public function admin_can_list_backups()
    {
        // Create some backup records
        BackupHistory::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/backups');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'file_name',
                            'type',
                            'status',
                            'size',
                            'size_formatted',
                            'created_by',
                            'created_at',
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function admin_can_get_backup_history()
    {
        // Create some backup records
        BackupHistory::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/backups/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'file_name',
                        'type',
                        'status',
                        'size',
                        'size_formatted',
                        'created_by',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function admin_can_upload_backup_file()
    {
        $file = UploadedFile::fake()->create('backup.sql.gz', 1024);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/backups/upload', [
                'file' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'file_name',
                    'type',
                    'size',
                    'size_formatted',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('backup_histories', [
            'type' => 'upload',
            'status' => 'success',
            'created_by' => $this->admin->id,
        ]);
    }

    /** @test */
    public function upload_requires_file()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/backups/upload', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function upload_validates_file_type()
    {
        $file = UploadedFile::fake()->create('backup.txt', 1024);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/backups/upload', [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function upload_validates_file_size()
    {
        // Create file larger than 500 MB (512000 KB)
        $file = UploadedFile::fake()->create('backup.sql.gz', 600000);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/backups/upload', [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function admin_can_download_backup()
    {
        // Create a backup record
        $backup = BackupHistory::factory()->create([
            'file_name' => 'test_backup.sql.gz',
            'file_path' => 'backups/test_backup.sql.gz',
            'status' => 'success',
        ]);

        // Create the actual file
        Storage::disk('local')->put($backup->file_path, 'test backup content');

        $response = $this->actingAs($this->admin)
            ->get("/api/admin/backups/{$backup->id}/download");

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=test_backup.sql.gz');
    }

    /** @test */
    public function download_returns_404_for_nonexistent_backup()
    {
        $response = $this->actingAs($this->admin)
            ->get('/api/admin/backups/999/download');

        $response->assertStatus(404);
    }

    /** @test */
    public function guest_cannot_access_backup_endpoints()
    {
        $response = $this->getJson('/api/admin/backups');
        $response->assertStatus(401);

        $response = $this->getJson('/api/admin/backups/history');
        $response->assertStatus(401);

        $response = $this->postJson('/api/admin/backups/upload');
        $response->assertStatus(401);
    }

    /** @test */
    public function non_admin_cannot_access_backup_endpoints()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/admin/backups');
        
        $response->assertStatus(403);
    }
}
