<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('limits repeated authentication attempts', function () {
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $this->postJson('/api/login', ['email' => 'missing@example.com', 'password' => 'invalid'])->assertUnprocessable();
    }
    $this->postJson('/api/login', ['email' => 'missing@example.com', 'password' => 'invalid'])->assertStatus(429);
});
