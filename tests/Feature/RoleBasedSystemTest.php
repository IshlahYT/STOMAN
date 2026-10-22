<?php

namespace Tests\Feature;

use App\Models\FileItem;
use App\Models\Folder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoleBasedSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $standardUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'role_id' => Role::ADMIN,
            'password' => Hash::make('password'),
        ]);

        $this->standardUser = User::factory()->create([
            'name' => 'Standard User',
            'email' => 'user@test.com',
            'role_id' => Role::USER,
            'password' => Hash::make('password'),
        ]);
    }

    /**
     * Requirement 1: Two user roles: Admin (id: 1) and User (id: 2).
     */
    public function test_roles_table_contains_admin_and_user_roles(): void
    {
        $adminRole = Role::find(Role::ADMIN);
        $userRole = Role::find(Role::USER);

        $this->assertNotNull($adminRole);
        $this->assertEquals(1, $adminRole->id);
        $this->assertEquals('Admin', $adminRole->name);

        $this->assertNotNull($userRole);
        $this->assertEquals(2, $userRole->id);
        $this->assertEquals('User', $userRole->name);

        $this->assertTrue($this->admin->isAdmin());
        $this->assertFalse($this->admin->isUser());

        $this->assertTrue($this->standardUser->isUser());
        $this->assertFalse($this->standardUser->isAdmin());
    }

    /**
     * Requirement 2: Grant Admin full CRUD operations for managing folders and files.
     */
    public function test_admin_has_full_crud_operations_for_folders(): void
    {
        $this->actingAs($this->admin, 'web');

        // Create folder (Create)
        $createRes = $this->postJson(route('api.folders.store'), [
            'name' => 'Admin Projects',
            'color' => 'primary',
        ]);
        $createRes->assertStatus(201);
        $folderId = $createRes->json('folder.id');

        // View folder (Read)
        $showRes = $this->getJson(route('api.folders.show', $folderId));
        $showRes->assertStatus(200);
        $showRes->assertJsonPath('folder.name', 'Admin Projects');

        // Update folder (Update)
        $updateRes = $this->patchJson(route('api.folders.update', $folderId), [
            'name' => 'Admin Projects Renamed',
            'color' => 'violet',
        ]);
        $updateRes->assertStatus(200);
        $this->assertDatabaseHas('folders', ['id' => $folderId, 'name' => 'Admin Projects Renamed']);

        // Delete folder (Delete)
        $deleteRes = $this->deleteJson(route('api.folders.destroy', $folderId));
        $deleteRes->assertStatus(200);
        $this->assertDatabaseHas('folders', ['id' => $folderId, 'is_trashed' => 1]);
    }

    public function test_admin_has_full_crud_operations_for_files(): void
    {
        $this->actingAs($this->admin, 'web');

        // Create file (Upload)
        $fakeFile = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');
        $uploadRes = $this->postJson(route('api.files.upload'), [
            'file' => $fakeFile,
        ]);
        $uploadRes->assertStatus(201);
        $fileId = $uploadRes->json('file.id');

        // Read file (Download & Preview)
        $downloadRes = $this->get(route('api.files.download', $fileId));
        $downloadRes->assertStatus(200);

        $previewRes = $this->get(route('api.files.preview', $fileId));
        $previewRes->assertStatus(200);

        // Update file (Rename & Star)
        $renameRes = $this->patchJson(route('api.items.rename', $fileId), ['name' => 'updated-report.pdf']);
        $renameRes->assertStatus(200);
        $this->assertDatabaseHas('files', ['id' => $fileId, 'name' => 'updated-report.pdf']);

        $starRes = $this->patchJson(route('api.items.star', $fileId), ['starred' => true]);
        $starRes->assertStatus(200);
        $this->assertDatabaseHas('files', ['id' => $fileId, 'is_starred' => 1]);

        // Delete file (Trash & Permanent Delete)
        $trashRes = $this->deleteJson(route('api.items.destroy'), ['ids' => [$fileId]]);
        $trashRes->assertStatus(200);
        $this->assertDatabaseHas('files', ['id' => $fileId, 'is_trashed' => 1]);
    }

    /**
     * Requirement 3: Restrict the User to read-only permissions for viewing files.
     */
    public function test_standard_user_can_view_and_download_files(): void
    {
        $this->actingAs($this->standardUser, 'web');

        // Create a sample file
        $file = FileItem::create([
            'user_id' => $this->standardUser->id,
            'name' => 'viewable.pdf',
            'path' => 'uploads/viewable.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
        ]);
        Storage::disk('local')->put('uploads/viewable.pdf', 'Sample PDF Content');

        // Read-only user can list items
        $listRes = $this->getJson(route('api.items.list'));
        $listRes->assertStatus(200);

        // Read-only user can download file
        $downloadRes = $this->get(route('api.files.download', $file->id));
        $downloadRes->assertStatus(200);

        // Read-only user can preview file
        $previewRes = $this->get(route('api.files.preview', $file->id));
        $previewRes->assertStatus(200);
    }

    public function test_standard_user_is_forbidden_from_creating_or_modifying_files_and_folders(): void
    {
        $this->actingAs($this->standardUser, 'web');

        // 1. Cannot upload files (returns 403 Forbidden)
        $fakeFile = UploadedFile::fake()->create('attempt.pdf', 500);
        $uploadRes = $this->postJson(route('api.files.upload'), ['file' => $fakeFile]);
        $uploadRes->assertStatus(403);

        // 2. Cannot create folders (returns 403 Forbidden)
        $createFolderRes = $this->postJson(route('api.folders.store'), ['name' => 'Forbidden Folder']);
        $createFolderRes->assertStatus(403);

        // Create an existing folder and file directly to test mutate restrictions
        $folder = Folder::create([
            'user_id' => $this->standardUser->id,
            'name' => 'Test Folder',
            'color' => 'primary',
        ]);
        $file = FileItem::create([
            'user_id' => $this->standardUser->id,
            'name' => 'Test File.txt',
            'path' => 'uploads/test.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
        ]);

        // 3. Cannot update folder
        $updateFolderRes = $this->patchJson(route('api.folders.update', $folder->id), ['name' => 'New Name']);
        $updateFolderRes->assertStatus(403);

        // 4. Cannot delete folder
        $deleteFolderRes = $this->deleteJson(route('api.folders.destroy', $folder->id));
        $deleteFolderRes->assertStatus(403);

        // 5. Cannot rename items
        $renameRes = $this->patchJson(route('api.items.rename', $file->id), ['name' => 'Hacked.txt']);
        $renameRes->assertStatus(403);

        // 6. Cannot star items
        $starRes = $this->patchJson(route('api.items.star', $file->id), ['starred' => true]);
        $starRes->assertStatus(403);

        // 7. Cannot delete/trash items
        $destroyRes = $this->deleteJson(route('api.items.destroy'), ['ids' => [$file->id]]);
        $destroyRes->assertStatus(403);

        // 8. Cannot move items
        $moveRes = $this->postJson(route('api.items.move'), ['ids' => [$file->id], 'target' => null]);
        $moveRes->assertStatus(403);

        // 9. Cannot archive items
        $archiveRes = $this->postJson(route('api.items.archive'), ['ids' => [$file->id]]);
        $archiveRes->assertStatus(403);
    }

    /**
     * Requirement 4: Create a 'User Management' page and restrict its access so that only Admin can view and use it.
     */
    public function test_guest_cannot_access_user_management(): void
    {
        $response = $this->get(route('users.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_standard_user_cannot_access_user_management(): void
    {
        $this->actingAs($this->standardUser, 'web');

        $response = $this->get(route('users.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_access_user_management_page(): void
    {
        $this->actingAs($this->admin, 'web');

        $response = $this->get(route('users.index'));
        $response->assertStatus(200);
        $response->assertSee('User Management');
        $response->assertSee('Admin User');
        $response->assertSee('Standard User');
    }

    public function test_admin_can_create_user_via_user_management(): void
    {
        $this->actingAs($this->admin, 'web');

        $response = $this->post(route('users.store'), [
            'name' => 'New Colleague',
            'email' => 'colleague@test.com',
            'password' => 'secret123',
            'role_id' => Role::USER,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'colleague@test.com',
            'role_id' => Role::USER,
        ]);
    }

    public function test_admin_can_update_user_role(): void
    {
        $this->actingAs($this->admin, 'web');

        $targetUser = User::factory()->create([
            'name' => 'Promoted User',
            'email' => 'promote@test.com',
            'role_id' => Role::USER,
        ]);

        $response = $this->put(route('users.update', $targetUser->id), [
            'name' => 'Promoted User',
            'email' => 'promote@test.com',
            'role_id' => Role::ADMIN,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertEquals(Role::ADMIN, $targetUser->fresh()->role_id);
    }

    public function test_admin_can_delete_user(): void
    {
        $this->actingAs($this->admin, 'web');

        $targetUser = User::factory()->create([
            'email' => 'to-delete@test.com',
            'role_id' => Role::USER,
        ]);

        $response = $this->delete(route('users.destroy', $targetUser->id));
        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $this->actingAs($this->admin, 'web');

        $response = $this->delete(route('users.destroy', $this->admin->id));
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }
}
