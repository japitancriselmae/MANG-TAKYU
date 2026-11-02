<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ForwardedHttpsTest extends TestCase
{
    public function test_render_forwarded_https_is_detected_by_laravel(): void
    {
        Route::get('/_test/request-scheme', fn (Request $request): string => $request->getScheme());

        $response = $this->withHeader('X-Forwarded-Proto', 'https')
            ->get('/_test/request-scheme');

        $response->assertOk()
            ->assertSeeText('https');
    }

    public function test_http_is_preserved_when_no_forwarded_https_header_is_present(): void
    {
        Route::get('/_test/request-scheme', fn (Request $request): string => $request->getScheme());

        $response = $this->withServerVariables([
            'HTTPS' => 'off',
            'SERVER_PORT' => 80,
        ])->get('/_test/request-scheme');

        $response->assertOk()
            ->assertSeeText('http');
    }
}
