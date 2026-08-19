<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Tests\Data;

use Illuminate\Validation\ValidationException;
use JustBetter\DynamicsClient\Data\TokenData;
use JustBetter\DynamicsClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class TokenDataTest extends TestCase
{
    #[Test]
    public function it_can_expose_a_token(): void
    {
        $token = TokenData::make([
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'access_token' => '::access-token::',
        ])->validate();

        $this->assertSame('Bearer', $token->tokenType());
        $this->assertSame(3600, $token->expiresIn());
        $this->assertSame('::access-token::', $token->accessToken());
    }

    #[Test]
    public function it_can_throw_an_exception_for_an_incomplete_token(): void
    {
        $this->expectException(ValidationException::class);

        TokenData::make(['token_type' => 'Bearer'])->validate();
    }
}
