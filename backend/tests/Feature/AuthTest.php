<?php

use App\Models\User;

describe('POST /api/login', function () {
    it('returns a token and user on valid credentials', function () {
        $user = User::factory()->create([
            'tenant_id' => test()->tenant->id,
            'password'  => bcrypt('Classhub1234'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'Classhub1234',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'roles']]);
    });

    it('returns 401 on wrong password', function () {
        $user = User::factory()->create([
            'tenant_id' => test()->tenant->id,
        ]);

        $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'wrongpassword',
        ])->assertStatus(401);
    });

    it('returns 422 on missing fields', function () {
        $this->postJson('/api/login', [])->assertUnprocessable();
    });
});

describe('POST /api/logout', function () {
    it('revokes the token and removes it from the database', function () {
        $user      = User::factory()->create(['tenant_id' => test()->tenant->id]);
        $newToken  = $user->createToken('api');
        $tokenId   = $newToken->accessToken->id;

        $this->withToken($newToken->plainTextToken)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJson(['message' => 'Logged out successfully.']);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    });
});

describe('GET /api/user', function () {
    it('returns the authenticated user with tenant', function () {
        actingAsRole('teacher');

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'roles', 'tenant' => ['id', 'name']],
            ]);
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson('/api/user')->assertUnauthorized();
    });
});
