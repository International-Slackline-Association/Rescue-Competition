<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_pages_send_security_headers(): void
    {
        $this->get('/rules')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_hsts_is_sent_over_https(): void
    {
        $this->get('https://localhost/rules')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_error_responses_send_security_headers_too(): void
    {
        $this->get('/does-not-exist')->assertNotFound()->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
