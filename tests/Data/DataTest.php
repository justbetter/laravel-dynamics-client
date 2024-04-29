<?php

namespace JustBetter\DynamicsClient\Tests\Data;

use Illuminate\Validation\ValidationException;
use JustBetter\DynamicsClient\Data\TokenData;
use JustBetter\DynamicsClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DataTest extends TestCase
{
    #[Test]
    public function it_can_interact_with_data(): void
    {
        $tokenData = TokenData::of([
            'token_type' => '::token-type::',
            'expires_in' => 3600,
            'ext_expires_in' => 3600,
            'access_token' => '::access-token::',
        ]);

        $this->assertTrue(isset($tokenData['access_token']));
        $this->assertEquals('::access-token::', $tokenData['access_token']);

        $tokenData['access_token'] = '::new-access-token::';

        $this->assertEquals('::new-access-token::', $tokenData['access_token']);

        unset($tokenData['access_token']);

        $this->assertNull($tokenData['access_token']);
    }

    #[Test]
    public function it_can_throw_exceptions(): void
    {
        $this->expectException(ValidationException::class);

        TokenData::of([]);
    }
}
