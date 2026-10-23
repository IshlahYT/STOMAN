<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Login redirection: a successful login must establish a real web session
 * (cookie) and land the user on their Dashboard — never bounce back to /login.
 */
class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $standardUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'role_id' => Role::ADMIN,
            'password' => bcrypt('secret123'),
        ]);

        $this->standardUser = User::factory()->create([
            'name' => 'Standard User',
            'email' => 'user@test.com',
            'role_id' => Role::USER,
            'password' => bcrypt('secret123'),
        ]);
    }

    public function test_successful_login_redirects_admin_to_dashboard(): void
    {
        $response = $this->post(route('login'), [
            'email' => $this->admin->email,
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($this->admin, 'web');
    }

    public function test_successful_login_redirects_regular_user_to_dashboard(): void
    {
        $response = $this->post(route('login'), [
            'email' => $this->standardUser->email,
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($this->standardUser, 'web');
    }

    /**
     * The session must survive the redirect: the dashboard actually loads
     * (this is the "stuck on /login" regression — an unauthenticated
     * browser gets bounced back to the login page).
     */
    public function test_dashboard_loads_after_login_instead_of_bouncing_back_to_login(): void
    {
        $this->post(route('login'), [
            'email' => $this->admin->email,
            'password' => 'secret123',
        ]);

        $this->get(route('home'))->assertOk();
        $this->get(route('storage.index'))->assertOk();
    }

    /**
     * Guests that were sent to login from a protected page land back on it.
     */
    public function test_login_redirects_to_the_previously_requested_page(): void
    {
        $this->get(route('settings.index'))->assertRedirect(route('login'));

        $response = $this->post(route('login'), [
            'email' => $this->admin->email,
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('settings.index'));
    }

    public function test_failed_login_returns_to_the_login_page_with_an_error(): void
    {
        // Simulate a browser: the failing POST comes from the login page.
        $response = $this->withHeaders(['Referer' => route('login')])
            ->post(route('login'), [
                'email' => $this->admin->email,
                'password' => 'wrong-password',
            ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }

    public function test_registration_redirects_to_dashboard_and_authenticates(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'New Person',
            'email' => 'newperson@test.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated('web');

        $this->get(route('home'))->assertOk();
    }
}
