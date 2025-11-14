<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    required: ['id', 'name', 'email'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
    ]
)]
class UserSchema
{
}

#[OA\Schema(
    schema: 'UserCreateRequest',
    required: ['name', 'email', 'password'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
    ]
)]
class UserCreateRequest
{
}

#[OA\Schema(
    schema: 'LoginRequest',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
    ]
)]
class LoginRequest
{
}

#[OA\Schema(
    schema: 'LoginResponse',
    required: ['user', 'token'],
    properties: [
        new OA\Property(
            property: 'user',
            ref: '#/components/schemas/User'
        ),
        new OA\Property(property: 'token', type: 'string', example: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'),
    ]
)]
class LoginResponse
{
}


