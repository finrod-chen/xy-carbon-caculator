<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_longer_than_255_characters_is_rejected(): void
    {
        $email = str_repeat('a', 250).'@example.com';

        $this->from('/login')->post('/login', [
            'email' => $email,
            'password' => 'whatever-1234',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
