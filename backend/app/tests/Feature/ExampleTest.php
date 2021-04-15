<?php

namespace Tests\Feature;

use App\Models\User;
use http\Exception\InvalidArgumentException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Prop\Core\Models\Role;
use Prop\Services\Mailman\Mailman;
use Tests\TestCase;

class ExampleTest extends TestCase {
    function mockService(string $name) {
        $mock = \Mockery::mock($name);
        $this->app->instance($name, $mock);
        return $mock;
    }

    function hitCreateAdmin(array $values = []): TestResponse {
        $defaults = [
            'first_name' => 'nikos',
            'last_name' => 'pap',
            'email' => 'nikos@pap.com',
            'password' => 'password',
        ];

        $body = array_merge($defaults, $values);
        return $this->post('api/user/create', $body);
    }

    function hitLogin(array $values = []): TestResponse {
        $defaults = [
            'email' => 'nikos@pap.com',
            'password' => 'password',
        ];

        $body = array_merge($defaults, $values);
        return $this->post('api/user/login', $body);
    }

    function assertStatusOK(TestResponse $response) {
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    function assertResponseStatus(int $status, TestResponse $response) {
        $this->assertEquals($status, $response->getStatusCode());
    }

    function test_admin_can_register() {
        $mock = $this->mockService(Mailman::class);
        $mock->shouldReceive('send')->once();

        $response = $this->hitCreateAdmin();
        $content = json_decode($response->getContent());

        $this->assertStatusOK($response);

        $this->assertEquals('nikos', $content->first_name);
        $this->assertEquals(true, $content->is_admin);
    }

    function test_cant_create_admin_with_same_email() {
        $this->hitCreateAdmin();

        $response = $this->hitCreateAdmin();
        $this->assertResponseStatus(Response::HTTP_CONFLICT, $response);
    }

    function test_can_login() {
        $this->hitCreateAdmin();

        $response = $this->hitLogin();
        $this->assertStatusOK($response);
        $content = json_decode($response->getContent());
        $this->assertNotEmpty($content->token);
    }

    function test_cant_login_with_wrong_email() {
        $this->hitCreateAdmin();

        $response = $this->hitLogin(['email' => 'foo@pap.com']);
        $this->assertResponseStatus(Response::HTTP_UNAUTHORIZED, $response);
    }

    function test_cant_login_with_wrong_password() {
        $this->hitCreateAdmin();

        $response = $this->hitLogin(['password' => 'not password']);
        $this->assertResponseStatus(Response::HTTP_UNAUTHORIZED, $response);
    }

    function test_can_reset_password() {
        $response = $this->hitCreateAdmin();

        $mock = $this->mockService(Mailman::class);
        $mock->shouldReceive('send')->once();

        $this->post('api/user/forgot-password', ['email' => 'nikos@pap.com']);
        $this->assertStatusOK($response);

        // User goes to link and resets password.
        $token = DB::table('password_resets')->first()->token;
        $response = $this->post('api/user/reset-password', [
            'password' => 'new-password',
            'token' => $token,
        ]);
        $this->assertStatusOK($response);

        $response = $this->hitLogin(['password' => 'new-password']);
        $this->assertStatusOK($response);

        // User tries to reset password again.
        $response = $this->post('api/user/reset-password', [
            'token' => $token,
            'password' => 'new-password'
        ]);
        $this->assertResponseStatus(Response::HTTP_CONFLICT, $response);
    }

    function test_create_association_needs_authentication() {
        // No token
        $this->hitCreateAdmin();

        $token = User::where('email', 'nikos@pap.com')->first()->remember_token;
        $response = $this->post('api/association/create', [
            'name' => 'Amazing association',
            'address' => 'Foovej 11, Barstrup',
        ], ['token' => $token]);
        $this->assertResponseStatus(Response::HTTP_UNAUTHORIZED, $response);

        // Token but no user with this token
        $response = $this->post('api/association/create', [
            'name' => 'Amazing association',
            'address' => 'Foovej 11, Barstrup',
        ], ['token' => 'jibberish']);
        $this->assertResponseStatus(Response::HTTP_UNAUTHORIZED, $response);
    }

    function test_admin_can_create_association() {
        $this->hitCreateAdmin();
        $this->hitLogin(['password' => 'password']);

        $token = User::where('email', 'nikos@pap.com')->first()->remember_token;

        $response = $this->post('api/association/create', [
            'name' => 'Amazing association',
            'address' => 'Foovej 11, Barstrup',
        ], ['token' => $token]);

        $this->assertStatusOK($response);

        $content = json_decode($response->getContent());
        $this->assertEquals('Amazing association', $content->name);
        $this->assertEquals('Foovej 11, Barstrup', $content->address);
    }

    function test_admin_cant_create_multiple_associations() {
        $this->hitCreateAdmin();
        $this->hitLogin(['password' => 'password']);

        $token = User::where('email', 'nikos@pap.com')->first()->remember_token;

        $response = $this->post('api/association/create', [
            'name' => 'Amazing association',
            'address' => 'Foovej 11, Barstrup',
        ], ['token' => $token]);

        $this->assertStatusOK($response);

        $content = json_decode($response->getContent());
        $this->assertEquals('Amazing association', $content->name);
        $this->assertEquals('Foovej 11, Barstrup', $content->address);

        $response = $this->post('api/association/create', [
            'name' => 'Amazing association',
            'address' => 'Foovej 11, Barstrup',
        ], ['token' => $token]);
        $this->assertResponseStatus(Response::HTTP_CONFLICT, $response);
    }
}
