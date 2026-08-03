<?php

use App\Models\Screen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('a user can create a new screen and it becomes the selected screen', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test('admin.dashboard')
        ->set('newScreenName', 'Matron\'s House')
        ->call('createScreen')
        ->assertSet('creatingScreen', false);

    $screen = Screen::query()->where('name', "Matron's House")->firstOrFail();

    expect($user->fresh()->screens()->pluck('screens.id'))->toContain($screen->id);
});

test('a user with multiple screens can switch between them', function () {
    $user = User::factory()->create();
    $screenA = Screen::factory()->create(['name' => 'Screen A']);
    $screenB = Screen::factory()->create(['name' => 'Screen B']);
    $screenA->users()->attach($user);
    $screenB->users()->attach($user);

    Livewire::actingAs($user)->test('admin.dashboard')
        ->call('selectScreen', $screenB->id)
        ->assertSet('screenId', $screenB->id);
});

test('a user can share a screen with another existing user by email', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $screen = Screen::factory()->create();
    $screen->users()->attach($owner);

    Livewire::actingAs($owner)->test('admin.dashboard')
        ->set('shareEmail', $collaborator->email)
        ->call('shareScreen')
        ->assertHasNoErrors();

    expect($screen->users()->pluck('users.id'))->toContain($collaborator->id);
});

test('sharing a screen with an unknown email shows a validation error', function () {
    $owner = User::factory()->create();
    $screen = Screen::factory()->create();
    $screen->users()->attach($owner);

    Livewire::actingAs($owner)->test('admin.dashboard')
        ->set('shareEmail', 'nobody@example.com')
        ->call('shareScreen')
        ->assertHasErrors('shareEmail');
});

test('a user can remove another user from a shared screen', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $screen = Screen::factory()->create();
    $screen->users()->attach([$owner->id, $collaborator->id]);

    Livewire::actingAs($owner)->test('admin.dashboard')
        ->call('removeUser', $collaborator->id);

    expect($screen->users()->pluck('users.id'))->not->toContain($collaborator->id);
});
