<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Unit;
use App\Models\User;
use http\Exception\InvalidArgumentException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Prop\Core\Models\Role;
use Prop\Services\Mailman\Mailman;
use Tests\TestCase;
use function PHPUnit\Framework\assertEquals;

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
        $this->hitCreateAdmin();

        // No token
        $response = $this->post('api/association/create', [
            'name' => 'Amazing association',
            'address' => 'Foovej 11, Barstrup',
        ]);
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

    function test_create_unit_needs_authentication() {
        $this->hitCreateAdmin();

        // No token
        $response = $this->post('api/unit/create', [
            'address' => 'Foovej 11 A, Barstrup',
        ]);
        $this->assertResponseStatus(Response::HTTP_UNAUTHORIZED, $response);

        // Token but no user with this token
        $response = $this->post('api/unit/create', [
            'address' => 'Foovej 11 A, Barstrup',
        ], ['token' => 'jibberish']);
        $this->assertResponseStatus(Response::HTTP_UNAUTHORIZED, $response);
    }

    function test_admin_can_create_units() {
        $this->hitCreateAdmin();
        $this->hitLogin(['password' => 'password']);

        $token = User::where('email', 'nikos@pap.com')->first()->remember_token;

        $this->post('api/association/create', [
            'name' => 'Amazing association',
            'address' => 'Foovej 11, Barstrup',
        ], ['token' => $token]);

        $response = $this->post('api/unit/create', [
            'address' => 'Foovej 11 A, Barstrup',
        ], ['token' => $token]);

        $this->assertStatusOK($response);
        $content = json_decode($response->getContent());
        $this->assertEquals('Foovej 11 A, Barstrup', $content->address);

        $response = $this->post('api/unit/create', [
            'address' => 'Foovej 11 B, Barstrup',
        ], ['token' => $token]);
        $this->assertStatusOK($response);
        $content = json_decode($response->getContent());
        $this->assertEquals('Foovej 11 B, Barstrup', $content->address);
    }

    function test_admin_cant_create_units_with_same_address() {
        $this->hitCreateAdmin();
        $this->hitLogin(['password' => 'password']);

        $token = User::where('email', 'nikos@pap.com')->first()->remember_token;

        $this->post('api/association/create', [
            'name' => 'Amazing association',
            'address' => 'Foovej 11, Barstrup',
        ], ['token' => $token]);

        $response = $this->post('api/unit/create', [
            'address' => 'Foovej 11 A, Barstrup',
        ], ['token' => $token]);

        $this->assertStatusOK($response);
        $content = json_decode($response->getContent());
        $this->assertEquals('Foovej 11 A, Barstrup', $content->address);

        $response = $this->post('api/unit/create', [
            'address' => 'Foovej 11 A, Barstrup',
        ], ['token' => $token]);
        $this->assertResponseStatus(Response::HTTP_CONFLICT, $response);
    }

    function test_can_attach_owners_to_units_needs_authentication() {
        $this->hitCreateAdmin();

        // No token
        $response = $this->post('api/unit/assign-owner', [
            'unit_id' => 1,
            'email' => 'owner1@foo.com',
        ]);
        $this->assertResponseStatus(Response::HTTP_UNAUTHORIZED, $response);

        // Token but no user with this token
        $response = $this->post('api/unit/assign-owner', [
            'unit_id' => 1,
            'email' => 'owner1@foo.com',
        ], ['token' => 'jibberish']);
        $this->assertResponseStatus(Response::HTTP_UNAUTHORIZED, $response);
    }

    function test_admin_can_attach_owners_to_units() {
        $this->hitCreateAdmin();
        $this->hitLogin(['password' => 'password']);

        $token = User::where('email', 'nikos@pap.com')->first()->remember_token;

        $this->post('api/association/create', [
            'name' => 'Amazing association',
            'address' => 'Foovej 11, Barstrup',
        ], ['token' => $token]);

        // Attach unit to owner that doesnt exist...
        $response = $this->post('api/unit/create', [
            'address' => 'Foovej 11 A, Barstrup',
        ], ['token' => $token]);
        $unit_id = json_decode($response->getContent())->id;

        // ...Assert email is sent
        $mock = $this->mockService(Mailman::class);
        $mock->shouldReceive('send')->once()->withSomeOfArgs(
            'owner1@foo.com',
            'emails.invite_owner',
            'Come join',
        );

        $this->post('api/unit/assign-owner', [
            'unit_id' => $unit_id,
            'email' => 'owner1@foo.com',
        ], ['token' => $token]);

        // ...Assert user is created
        $user = User::where('email', 'owner1@foo.com')->first();
        $this->assertNotNull($user);

        // ...Assert invitation is created
        $this->assertNotNull(Invitation::where('user_id', $user->id)->first());

        // ...Assert unit is attached
        $this->assertTrue(Unit::find($unit_id)->owners->contains($user));

        // Attach another unit to owner that exists but hasnt accepted invitations yet...
        $response = $this->post('api/unit/create', [
            'address' => 'Foovej 11 B, Barstrup',
        ], ['token' => $token]);
        $unit_id = json_decode($response->getContent())->id;

        // ...Assert email is sent
        $mock = $this->mockService(Mailman::class);
        $mock->shouldReceive('send')->once()->withSomeOfArgs(
            'owner1@foo.com',
            'emails.invite_owner',
            'Come join',
        );

        $this->post('api/unit/assign-owner', [
            'unit_id' => $unit_id,
            'email' => 'owner1@foo.com',
        ], ['token' => $token]);

        // ...Assert invitation is created
        $invitation = Invitation::where('user_id', $user->id)->first();
        $this->assertNotNull($invitation);

        // ...Assert unit is attached
        $this->assertTrue(Unit::find($unit_id)->owners->contains($user));

        // Attach unit to owner that exists as a normal user...
        $response = $this->post('api/unit/create', [
            'address' => 'Foovej 11 C, Barstrup',
        ], ['token' => $token]);
        $unit_id = json_decode($response->getContent())->id;

        // ...User accepts invitation
        // ...Assert email is sent
        $mock = $this->mockService(Mailman::class);
        $mock->shouldReceive('send')->once()->withSomeOfArgs(
            'owner1@foo.com',
            'emails.welcome_owner',
            'Welcome',
        );

        $this->post('api/user/accept-invitation', [
            'token' => $invitation->token,
            'first_name' => 'mickey',
            'last_name' => 'mouse',
            'password' => 'password',
        ]);

        // ...Assert email is sent
        $mock = $this->mockService(Mailman::class);
        $mock->shouldReceive('send')->once()->withSomeOfArgs(
            'owner1@foo.com',
            'emails.unit_assigned'
        );

        $this->post('api/unit/assign-owner', [
            'unit_id' => $unit_id,
            'email' => 'owner1@foo.com',
        ], ['token' => $token]);

        // ...Assert invitation is not created
        $this->assertNull(Invitation::where('user_id', $user->id)->first());
    }

    function test_admin_can_attach_multiple_owners_to_multiple_unit() {
    }

    function test_owner_can_accept_invitation() {
        $this->hitCreateAdmin();
        $this->hitLogin(['password' => 'password']);

        $token = User::where('email', 'nikos@pap.com')->first()->remember_token;

        $this->post('api/association/create', [
            'name' => 'Amazing association',
            'address' => 'Foovej 11, Barstrup',
        ], ['token' => $token]);

        // Attach unit to owner that doesnt exist...
        $response = $this->post('api/unit/create', [
            'address' => 'Foovej 11 A, Barstrup',
        ], ['token' => $token]);
        $unit_id = json_decode($response->getContent())->id;

        // ...Assert email is sent
        $mock = $this->mockService(Mailman::class);
        $mock->shouldReceive('send')->once()->withSomeOfArgs(
            'owner1@foo.com',
            'emails.invite_owner',
            'Come join',
        );

        $this->post('api/unit/assign-owner', [
            'unit_id' => $unit_id,
            'email' => 'owner1@foo.com',
        ], ['token' => $token]);

        // Attach one more unit to the same owner...
        $response = $this->post('api/unit/create', [
            'address' => 'Foovej 11 B, Barstrup',
        ], ['token' => $token]);
        $unit_id = json_decode($response->getContent())->id;

        // ...Assert email is sent
        $mock = $this->mockService(Mailman::class);
        $mock->shouldReceive('send')->once()->withSomeOfArgs(
            'owner1@foo.com',
            'emails.invite_owner',
            'Come join',
        );

        $this->post('api/unit/assign-owner', [
            'unit_id' => $unit_id,
            'email' => 'owner1@foo.com',
        ], ['token' => $token]);

        // ...Assert invitations are created
        $user = User::where('email', 'owner1@foo.com')->first();
        $invitations = Invitation::where('user_id', $user->id)->get();
        $this->assertCount(2, $invitations);

        // User accepts invitation...
        // ...Assert email is sent
        $mock = $this->mockService(Mailman::class);
        $mock->shouldReceive('send')->once()->withSomeOfArgs(
            'owner1@foo.com',
            'emails.welcome_owner',
            'Welcome',
        );

        $response = $this->post('api/user/accept-invitation', [
            'token' => $invitations[0]->token,
            'first_name' => 'mickey',
            'last_name' => 'mouse',
            'password' => 'password',
        ]);

        // Assert  user is not dummy anymore
        $content = json_decode($response->getContent());
        $this->assertEquals('mickey', $content->first_name);
        $this->assertEquals('mouse', $content->last_name);

        // Assert all invitations are deleted
        $this->assertNull(Invitation::where('user_id', $user->id)->first());
    }

    function test_cant_attach_same_unit_to_same_owner() {
        $this->hitCreateAdmin();
        $this->hitLogin(['password' => 'password']);

        $token = User::where('email', 'nikos@pap.com')->first()->remember_token;

        $this->post('api/association/create', [
            'name' => 'Amazing association',
            'address' => 'Foovej 11, Barstrup',
        ], ['token' => $token]);

        $response = $this->post('api/unit/create', [
            'address' => 'Foovej 11 A, Barstrup',
        ], ['token' => $token]);
        $unit_id = json_decode($response->getContent())->id;


        $this->post('api/unit/assign-owner', [
            'unit_id' => $unit_id,
            'email' => 'owner1@foo.com',
        ], ['token' => $token]);

        $response = $this->post('api/unit/assign-owner', [
            'unit_id' => $unit_id,
            'email' => 'owner1@foo.com',
        ], ['token' => $token]);

        $this->assertResponseStatus(Response::HTTP_CONFLICT, $response);
    }
}
