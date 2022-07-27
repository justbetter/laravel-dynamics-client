<?php

namespace JustBetter\DynamicsClient\Tests\OData;

use JustBetter\DynamicsClient\OData\Pages\Customer;
use JustBetter\DynamicsClient\Tests\TestCase;

class BaseResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('dynamics.connections.::default::', [
            'base_url' => '::base_url::',
            'company' => '::company::',
            'username' => '::username::',
            'password' => '::password::',
            'auth' => '::auth::',
            'options' => [
                'connect_timeout' => 5,
            ],
        ]);

        config()->set('dynamics.connections.::other-connection::', [
            'base_url' => '::base_url::',
            'company' => '::company::',
            'username' => '::username::',
            'password' => '::password::',
            'auth' => '::auth::',
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
    public function it_can_set_the_connection(): void
    {
        $page = new Customer('::other-connection::');

        $this->assertEquals('::other-connection::', $page->connection);

        $page = Customer::new('::other-connection::');

        $this->assertEquals('::other-connection::', $page->connection);
    }
}
