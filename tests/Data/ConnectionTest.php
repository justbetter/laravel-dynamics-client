<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Tests\Data;

use JustBetter\DynamicsClient\Data\Connection;
use JustBetter\DynamicsClient\Exceptions\DynamicsException;
use JustBetter\DynamicsClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ConnectionTest extends TestCase
{
    #[Test]
    public function it_can_get_a_company_id(): void
    {
        $connection = Connection::make([
            'companies' => [
                'acme' => '::company-id::',
            ],
        ]);

        $this->assertSame('::company-id::', $connection->company('acme'));
    }

    #[Test]
    public function it_can_throw_an_exception_for_an_unknown_company(): void
    {
        $connection = Connection::make([
            'companies' => [
                'acme' => '::company-id::',
            ],
        ]);

        $this->expectException(DynamicsException::class);
        $this->expectExceptionMessage('Company "unknown" not found in the connection configuration.');

        $connection->company('unknown');
    }

    #[Test]
    public function it_can_throw_an_exception_for_a_company_without_an_id(): void
    {
        $connection = Connection::make([
            'companies' => [
                'acme' => null,
            ],
        ]);

        $this->expectException(DynamicsException::class);
        $this->expectExceptionMessage('Company "acme" has no ID configured in the connection configuration.');

        $connection->company('acme');
    }
}
