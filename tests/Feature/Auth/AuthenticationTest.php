<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Tymon\JWTAuth\JWTGuard;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $authService;

    public function __construct(string $name)
    {
        $this->authService = new AuthService();

        parent::__construct($name);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Event::fake();

        $response = $this->postJson(
            route(AuthService::AUTH_ROUTES_NAMES['login']),
            [
                'email' => $user->email,
                'password' => 'password',
            ],
        );

        Event::assertDispatched(Login::class);

        $response->assertOk();

        $response->assertJson(
            fn(AssertableJson $json) => $json
            ->has('access_token')
            ->whereType('access_token', 'string')
            ->where('token_type', 'bearer')
            ->has('expires_in')
            ->whereType('expires_in', 'integer'),
        );

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Event::fake();

        $response = $this->postJson(route(AuthService::AUTH_ROUTES_NAMES['login']), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        Event::assertNotDispatched(Login::class);

        $response->assertUnauthorized();

        $response->assertJson(
            fn(AssertableJson $json) => $json
            ->has('error')
            ->where('error', 'InvalidCredentialsException')
            ->has('message')
            ->whereType('message', 'string')
            ->etc(),
        );

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /* @var $auth JWTGuard */
        $auth = auth();

        $token = $auth->login($user);

        Event::fake();

        $response = $this
            // ->actingAs($user)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson(route(AuthService::AUTH_ROUTES_NAMES['logout']))
        ;

        Event::assertDispatched(Logout::class);

        $response->assertAccepted();

        $response->assertJson(
            fn(AssertableJson $json) => $json
            ->has('message')
            ->whereType('message', 'string')
            ->where('message', 'Successfully logged out'),
        );

        $this->assertGuest();
    }

    public function test_user_can_get_self_info(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /* @var $auth JWTGuard */
        $auth = auth();

        $token = $auth->login($user);

        $this->assertAuthenticated();

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson(route(AuthService::AUTH_ROUTES_NAMES['me']))
        ;

        $response->assertOk();

        $response->assertJson(
            fn(AssertableJson $json) => $json
            ->has('message')
            ->whereType('message', 'string')
            ->where('message', 'Current user fetched successfully')
            ->has('user')
            ->whereType('user', 'array'),
        );
    }
}
