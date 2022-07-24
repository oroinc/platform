<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;

class BusinessUnitSearchTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadBusinessUnit::class]);
    }

    public function testSearchBusinessUnits(): void
    {
        $buId = $this->getReference('business_unit')->getId();
        $response = $this->cget(
            ['entity' => 'search'],
            ['filter' => ['entities' => 'businessunits']]
        );
        $expectedContent = [
            'data' => [
                [
                    'type'          => 'search',
                    'id'            => 'businessunits-' . $buId,
                    'links'         => [
                        'entityUrl' => $this->getUrl('oro_business_unit_view', ['id' => $buId], true)
                    ],
                    'attributes'    => [
                        'entityName' => 'Main'
                    ],
                    'relationships' => [
                        'entity' => ['data' => ['type' => 'businessunits', 'id' => '<toString(@business_unit->id)>']]
                    ]
                ]
            ]
        ];

        $this->assertResponseContains($expectedContent, $response);
    }
}
