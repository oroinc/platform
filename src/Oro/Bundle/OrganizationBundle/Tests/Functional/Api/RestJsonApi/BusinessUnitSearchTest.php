<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;

class BusinessUnitSearchTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadBusinessUnit::class]);
        // do the reindex because by some unknown reasons the search index is empty
        // after upgrade from old application version
        self::getContainer()->get('oro_search.search.engine.indexer')->reindex(BusinessUnit::class);
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
