<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Tests\OData;

use JustBetter\DynamicsClient\OData\Pages\Customer;
use JustBetter\DynamicsClient\Tests\Fakes\OData\FakeResource;
use JustBetter\DynamicsClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class BaseResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('dynamics.resources', [
            Customer::class => '::customer-endpoint::',
        ]);

        config()->set('dynamics.connections.::default::', [
            'base_url' => '::base_url::',
            'version' => 'ODataV4',
            'company' => '::company::',
            'username' => '::username::',
            'password' => '::password::',
            'auth' => '::auth::',
            'page_size' => 1000,
            'options' => [
                'connect_timeout' => 5,
            ],
        ]);

        config()->set('dynamics.connections.::other-connection::', [
            'base_url' => '::base_url::',
            'version' => 'ODataV4',
            'company' => '::company::',
            'username' => '::username::',
            'password' => '::password::',
            'auth' => '::auth::',
            'page_size' => 1000,
            'options' => [
                'connect_timeout' => 5,
            ],
        ]);

        config()->set('dynamics.connection', '::default::');
    }

    #[Test]
    public function it_can_get_the_default_connection(): void
    {
        $page = new Customer;

        $this->assertSame('::default::', $page->connection);
    }

    #[Test]
    public function it_can_get_the_default_endpoint(): void
    {
        $page = new Customer;

        $this->assertSame('::customer-endpoint::', $page->endpoint);
    }

    #[Test]
    public function it_can_set_the_connection(): void
    {
        $page = new Customer('::other-connection::');

        $this->assertSame('::other-connection::', $page->connection);

        $page = Customer::new('::other-connection::');

        $this->assertSame('::other-connection::', $page->connection);
    }

    #[Test]
    public function it_can_set_the_endpoint(): void
    {
        $page = new Customer(null, '::other-endpoint::');

        $this->assertSame('::other-endpoint::', $page->endpoint);

        $page = Customer::new(null, '::other-endpoint::');

        $this->assertSame('::other-endpoint::', $page->endpoint);
    }

    #[Test]
    public function it_can_force_a_connection_and_endpoint(): void
    {
        $resource = FakeResource::new();

        $this->assertSame('::fake-connection::', $resource->connection);
        $this->assertSame('::fake-endpoint::', $resource->endpoint);
    }
}
