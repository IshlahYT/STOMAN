<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Smoke test: the app never bypasses authentication — guests are redirected
 * to the Login page, while auth pages and the QA error pages stay reachable.
 */
class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public static function pageRoutes(): array
    {
        return [
            // Protected pages: guests get redirected to Login.
            'home' => ['/', 302],
            'storage' => ['/storage', 302],
            'storage folder' => ['/storage/documents', 302],
            'shared' => ['/shared', 302],
            'recent' => ['/recent', 302],
            'starred' => ['/starred', 302],
            'archive' => ['/archive', 302],
            'trash' => ['/trash', 302],
            'settings' => ['/settings', 302],
            // Public pages.
            'login' => ['/login', 200],
            'register' => ['/register', 200],
            // Error pages, also directly accessible for review/QA.
            'error 403' => ['/errors/403', 403],
            'error 404' => ['/errors/404', 404],
            'error 500' => ['/errors/500', 500],
            'missing page' => ['/definitely-missing', 404],
        ];
    }

    #[DataProvider('pageRoutes')]
    public function test_pages_render(string $uri, int $status): void
    {
        $response = $this->get($uri);

        $response->assertStatus($status);

        if ($status === 302) {
            $response->assertRedirect(route('login'));

            return;
        }

        if ($status === 200) {
            $response->assertSee('STOMAN');
        }
    }

    public function test_pages_contain_no_raw_hex_colors(): void
    {
        foreach (self::pageRoutes() as [$uri, $status]) {
            $body = $this->get($uri)->getContent();

            // PRD 11: no raw hex values reach the markup.
            $this->assertDoesNotMatchRegularExpression(
                '/(?:style|class)="[^"]*#[0-9a-fA-F]{3,8}/',
                $body,
                "Raw hex color found in rendered markup of {$uri}",
            );
        }
    }
}
