<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class RegionTest extends RestJsonApiTestCase
{
    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'regions'],
            ['filter' => ['country' => 'GB'], 'page' => ['size' => 3]]
        );

        $this->assertResponseContains('cget_region.yml', $response);
    }

    public function testGetListFilterBySeveralIds()
    {
        $response = $this->cget(
            ['entity' => 'regions'],
            ['filter' => ['id' => 'GB-ABE,GB-ABD']]
        );

        $this->assertResponseContains('cget_region_filter_ids.yml', $response);
    }

    public function testGetListFilterBySeveralCountries()
    {
        $response = $this->cget(
            ['entity' => 'regions'],
            ['filter' => ['country' => 'MG,IL'], 'page' => ['size' => 100]]
        );

        $this->assertResponseContains('cget_region_filter_countries.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'regions', 'id' => 'GB-ABD']
        );

        $this->assertResponseContains('get_region.yml', $response);
    }

    public function testTryToCreate()
    {
        $response = $this->post(
            ['entity' => 'regions'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'regions', 'id' => 'GB-ABD'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'regions', 'id' => 'GB-ABD'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'regions', 'id' => 'GB-ABD'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceCountry()
    {
        $response = $this->getSubresource(
            ['entity' => 'regions', 'id' => 'IL-TA', 'association' => 'country']
        );

        $this->assertResponseContains('get_region_country.yml', $response);
    }

    public function testGetRelationshipCountry()
    {
        $response = $this->getRelationship(
            ['entity' => 'regions', 'id' => 'IL-TA', 'association' => 'country']
        );

        $this->assertResponseContains('get_region_country_id.yml', $response);
    }

    public function testTryToUpdateRelationshipCountry()
    {
        $response = $this->patchRelationship(
            ['entity' => 'regions', 'id' => 'IL-TA', 'association' => 'country'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
