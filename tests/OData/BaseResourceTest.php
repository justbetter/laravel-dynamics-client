<?php

namespace JustBetter\DynamicsClient\Tests\OData;

use JustBetter\DynamicsClient\OData\Pages\Customer;
use JustBetter\DynamicsClient\Tests\Fakes\OData\FakeResource;
use JustBetter\DynamicsClient\Tests\TestCase;

class BaseResourceTest extends TestCase
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

    /** @test */
    public function it_can_get_the_default_connection(): void
    {
        $page = new Customer();

        $this->assertEquals('::default::', $page->connection);
    }

    /** @test */
    public function it_can_get_the_default_endpoint(): void
    {
        $page = new Customer();

        $this->assertEquals('::customer-endpoint::', $page->endpoint);
    }

    /** @test */
    public function it_can_set_the_connection(): void
    {
        $page = new Customer('::other-connection::');

        $this->assertEquals('::other-connection::', $page->connection);

        $page = Customer::new('::other-connection::');

        $this->assertEquals('::other-connection::', $page->connection);
    }

    /** @test */
    public function it_can_set_the_endpoint(): void
    {
        $page = new Customer(null, '::other-endpoint::');

        $this->assertEquals('::other-endpoint::', $page->endpoint);

        $page = Customer::new(null, '::other-endpoint::');

        $this->assertEquals('::other-endpoint::', $page->endpoint);
    }

    /** @test */
    public function it_can_force_a_connection_and_endpoint(): void
    {
        $resource = FakeResource::new();

        $this->assertEquals('::fake-connection::', $resource->connection);
        $this->assertEquals('::fake-endpoint::', $resource->endpoint);
    }
}
