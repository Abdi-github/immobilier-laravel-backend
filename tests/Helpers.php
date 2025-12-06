<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use Database\Factories\AgencyFactory;
use Database\Factories\CantonFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\CityFactory;
use Database\Factories\PropertyFactory;
use Database\Factories\UserFactory;

function createUser(array $attributes = []): User
{
    return UserFactory::new()->create($attributes);
}

function createAdmin(array $attributes = []): User
{
    return UserFactory::new()->admin()->create($attributes);
}

function createAgent(array $attributes = []): User
{
    $canton = CantonFactory::new()->create();
    $city = CityFactory::new()->create(['canton_id' => $canton->id]);
    $agency = AgencyFactory::new()->create(['canton_id' => $canton->id, 'city_id' => $city->id]);

    return UserFactory::new()->agent()->create(array_merge(['agency_id' => $agency->id], $attributes));
}

function createAgencyAdmin(array $attributes = []): User
{
    $canton = CantonFactory::new()->create();
    $city = CityFactory::new()->create(['canton_id' => $canton->id]);
    $agency = AgencyFactory::new()->create(['canton_id' => $canton->id, 'city_id' => $city->id]);

    return UserFactory::new()->agencyAdmin()->create(array_merge(['agency_id' => $agency->id], $attributes));
}

function createProperty(array $attributes = []): \App\Domain\Property\Models\Property
{
    return PropertyFactory::new()->create($attributes);
}

function createPublishedProperty(array $attributes = []): \App\Domain\Property\Models\Property
{
    return PropertyFactory::new()->published()->create($attributes);
}

function loginAs(User $user): array
{
    $token = $user->createToken('test-token', ['*']);

    return [
        'Authorization' => 'Bearer ' . $token->plainTextToken,
        'Accept' => 'application/json',
    ];
}
