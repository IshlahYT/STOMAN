<?php

namespace Tests\Feature;

use App\Models\FileItem;
use App\Models\Folder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RBAC & route protection (PRD):
 *  - Admin sees every sidebar entry and can access every page.
 *  - Regular users strictly see only "My Folder" and are blocked (by
 *    middleware) from reaching any other page via URL.
 *  - The root route never bypasses authentication: guests land on Login.
 */
class RouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $standardUser;

    /** @var array<int, string> */
    protected array $protectedPages = [
        '/storage',
        '/recent',
        '/starred',
        '/archive',
        '/trash',
        '/shared',
        '/settings',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'role_id' => Role::ADMIN,
        ]);

        $this->standardUser = User::factory()->create([
            'name' => 'Standard User',
            'email' => 'user@test.com',
            'role_id' => Role::USER,
        ]);
    }

    /**
     * Default landing page: "/" must redirect guests to the Login page.
     */
    public function test_guest_opening_root_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_any_app_page_via_url(): void
    {
        foreach ($this->protectedPages as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
    }

    /**
     * Sidebar for a regular user: only "My Folder" — no other navigation.
     */
    public function test_regular_user_sidebar_shows_only_my_folder(): void
    {
        $this->actingAs($this->standardUser, 'web');

        $response = $this->get(route('home'));
        $response->assertOk();

        // The single menu entry is "My Folder" (pointing at the dashboard).
        $response->assertSee(__('stoman.nav.my_folder'));

        // No other navigation links are rendered anywhere in the sidebar.
        foreach (['/storage', '/recent', '/archive', '/trash', '/users', '/settings'] as $path) {
            $response->assertDontSee(url($path));
        }

        $response->assertDontSee(__('stoman.nav.storage'));  // "My Storage"
        $response->assertDontSee(__('stoman.nav.users'));    // "User Management"
        $response->assertDontSee(__('stoman.nav.settings')); // "Settings"
        $response->assertDontSee(__('stoman.account.guest'));
        $response->assertDontSee(__('stoman.account.not_signed_in'));
    }

    /**
     * Sidebar shows the authenticated user's real name and email.
     */
    public function test_sidebar_displays_authenticated_user_name_and_email(): void
    {
        $this->actingAs($this->standardUser, 'web');

        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('Standard User');
        $response->assertSee('user@test.com');
        $response->assertDontSee(__('stoman.account.guest'));       // no 'Guest'
        $response->assertDontSee(__('stoman.account.not_signed_in')); // no 'Not signed in'

        $this->actingAs($this->admin, 'web');

        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('Admin User');
        $response->assertSee('admin@test.com');
    }

    /**
     * Strict URL protection: a regular user cannot open any other page.
     */
    public function test_regular_user_is_redirected_home_from_every_other_route(): void
    {
        $this->actingAs($this->standardUser, 'web');

        foreach ($this->protectedPages as $uri) {
            $this->get($uri)->assertRedirect(route('home'));
        }
    }

    public function test_regular_user_cannot_access_user_management(): void
    {
        $this->actingAs($this->standardUser, 'web');

        $this->get(route('users.index'))->assertStatus(403);
    }

    /**
     * Regular-user dashboard: only folders and files created by Admins.
     */
    public function test_regular_user_dashboard_only_shows_admin_created_items(): void
    {
        FileItem::create([
            'user_id' => $this->admin->id,
            'name' => 'admin-owned.pdf',
            'path' => 'uploads/admin-owned.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);
        Folder::create(['user_id' => $this->admin->id, 'name' => 'Admin Folder', 'color' => 'primary']);

        FileItem::create([
            'user_id' => $this->standardUser->id,
            'name' => 'user-owned.pdf',
            'path' => 'uploads/user-owned.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);
        Folder::create(['user_id' => $this->standardUser->id, 'name' => 'User Folder', 'color' => 'primary']);

        $this->actingAs($this->standardUser, 'web');

        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('admin-owned.pdf');
        $response->assertSee('Admin Folder');
        $response->assertDontSee('user-owned.pdf');
        $response->assertDontSee('User Folder');
    }

    /**
     * Admin: full sidebar and access to every page.
     */
    public function test_admin_sees_all_sidebar_items(): void
    {
        $this->actingAs($this->admin, 'web');

        $response = $this->get(route('home'));
        $response->assertOk();

        $response->assertSee(__('stoman.nav.home'));
        $response->assertSee(__('stoman.nav.storage'));
        $response->assertSee(__('stoman.nav.recent'));
        $response->assertSee(__('stoman.nav.archive'));
        $response->assertSee(__('stoman.nav.trash'));
        $response->assertSee(__('stoman.nav.users'));
        $response->assertDontSee(__('stoman.nav.my_folder'));
    }

    public function test_admin_can_access_every_page(): void
    {
        $this->actingAs($this->admin, 'web');

        $this->get(route('home'))->assertOk();
        $this->get(route('settings.index'))->assertOk();

        foreach ($this->protectedPages as $uri) {
            $this->get($uri)->assertOk();
        }
    }
}
