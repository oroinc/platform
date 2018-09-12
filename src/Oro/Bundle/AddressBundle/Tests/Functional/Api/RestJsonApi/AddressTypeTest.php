<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class AddressTypeTest extends RestJsonApiTestCase
{
    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'addresstypes']
        );

        $this->assertResponseContains('cget_address_type.yml', $response);
    }

    public function testGetListFilterBySeveralIds()
    {
        $response = $this->cget(
            ['entity' => 'addresstypes'],
            ['filter' => ['id' => 'billing,shipping']]
        );

        $this->assertResponseContains('cget_address_type_filter_ids.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'addresstypes', 'id' => 'shipping']
        );

        $this->assertResponseContains('get_address_type.yml', $response);
    }

    public function testTryToCreate()
    {
        $response = $this->post(
            ['entity' => 'addresstypes'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 405);
        self::assertEquals('GET', $response->headers->get('Allow'));
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'addresstypes', 'id' => 'shipping'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 405);
        self::assertEquals('GET', $response->headers->get('Allow'));
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'addresstypes', 'id' => 'shipping'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 405);
        self::assertEquals('GET', $response->headers->get('Allow'));
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'addresstypes', 'id' => 'shipping'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 405);
        self::assertEquals('GET', $response->headers->get('Allow'));
    }
}
