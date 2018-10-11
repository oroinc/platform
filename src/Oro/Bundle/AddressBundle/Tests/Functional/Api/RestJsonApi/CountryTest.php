<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class CountryTest extends RestJsonApiTestCase
{
    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'countries'],
            ['filter' => ['id' => 'IL']]
        );

        $this->assertResponseContains('cget_country.yml', $response);
    }

    public function testGetListFilterBySeveralIds()
    {
        $response = $this->cget(
            ['entity' => 'countries'],
            ['filter' => ['id' => 'IL,MG']]
        );

        $this->assertResponseContains('cget_country_filter_ids.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'countries', 'id' => 'IL']
        );

        $this->assertResponseContains('get_country.yml', $response);
    }

    public function testTryToCreate()
    {
        $response = $this->post(
            ['entity' => 'countries'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'countries', 'id' => 'GB'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'countries', 'id' => 'GB'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'countries', 'id' => 'GB'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceRegions()
    {
        $response = $this->getSubresource(
            ['entity' => 'countries', 'id' => 'IL', 'association' => 'regions']
        );

        $this->assertResponseContains('get_country_regions.yml', $response);
    }

    public function testGetRelationshipRegions()
    {
        $response = $this->getRelationship(
            ['entity' => 'countries', 'id' => 'IL', 'association' => 'regions']
        );

        $this->assertResponseContains('get_country_regions_id.yml', $response);
    }

    public function testTryToUpdateRelationshipRegions()
    {
        $response = $this->patchRelationship(
            ['entity' => 'countries', 'id' => 'IL', 'association' => 'regions'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationshipRegions()
    {
        $response = $this->postRelationship(
            ['entity' => 'countries', 'id' => 'IL', 'association' => 'regions'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationshipRegions()
    {
        $response = $this->deleteRelationship(
            ['entity' => 'countries', 'id' => 'IL', 'association' => 'regions'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
