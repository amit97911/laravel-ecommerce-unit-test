<?php

namespace Tests\Unit;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    public $user_data = [
        'name' => 'Amit Patel',
        'email' => 'amit1@example.com',
        'password' => 'password123',
        'status' => 'active',
        'role' => 'user',
    ];

    public $url = '/user/login';

    /** @test */
    public function logs_in_a_user_successfully()
    {
        $user = User::create(array_merge($this->user_data, ['password' => bcrypt($this->user_data['password'])]));

        $response = $this->post($this->url, [
            'email' => $this->user_data['email'],
            'password' => $this->user_data['password'],
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function fails_login_with_invalid_credentials()
    {
        $user = User::create(array_merge($this->user_data, ['password' => bcrypt($this->user_data['password'])]));

        $response = $this->post($this->url, [
            'email' => $this->user_data['email'],
            'password' => 'wrongpassword',
        ]);

        if ($response->assertSessionHas('error', 'Invalid email and password please try again!')) {
            $this->assertTrue(true);
        }

        $response->assertRedirect();
        $this->assertGuest();
    }

    /** @test */
    public function fails_login_if_user_status_is_not_active()
    {
        $this->user_data['status'] = 'inactive';
        $user = User::create(array_merge($this->user_data, ['password' => bcrypt($this->user_data['password'])]));

        $response = $this->post($this->url, $this->user_data);

        if ($response->assertSessionHas('error', 'Invalid email and password please try again!')) {
            $this->assertTrue(true);
        }
        $response->assertRedirect();
        $this->assertGuest();
    }
}
