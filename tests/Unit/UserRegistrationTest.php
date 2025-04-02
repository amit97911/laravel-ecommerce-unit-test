<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private $user_data = [
        'name' => 'Amit Patel',
        'email' => 'amit1@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    private $url = '/user/register';

    /** @test */
    public function registers_a_user_successfully()
    {
        $response = $this->post($this->url, $this->user_data);
        $response->assertStatus(302);
        $this->assertDatabaseHas('users', ['email' => $this->user_data['email']]);
        $response->assertRedirect(route('home'));
        $this->assertEquals($this->user_data['email'], Session::get('user'));
    }

    /** @test */
    public function when_name_is_too_short()
    {
        $this->user_data['name'] = 'A'; // Name is too short
        $response = $this->post($this->url, $this->user_data);

        if ($response->assertSessionHasErrors('name')) {
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function when_email_is_already_taken()
    {
        $this->registers_a_user_successfully(); // Register a user first
        $response = $this->post($this->url, $this->user_data);

        if ($response->assertSessionHasErrors('email')) {
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function when_password_is_too_short()
    {
        $this->user_data['password'] = 'pass'; // Password is too short
        $response = $this->post($this->url, $this->user_data);

        if ($response->assertSessionHasErrors('password')) {
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function when_passwords_do_not_match()
    {
        $this->user_data['password_confirmation'] = 'wrongpassword';
        $response = $this->post($this->url, $this->user_data);

        if ($response->assertSessionHasErrors('password')) {
            $this->assertTrue(true);
        }
    }
}
