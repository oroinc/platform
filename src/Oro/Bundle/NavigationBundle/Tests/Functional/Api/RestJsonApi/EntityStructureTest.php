<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;

/**
 * Checks that a menu item is marked as excluded in the entity structure: it is a system record managed on the page
 * of its menu, so it should not be offered as an entity to work with.
 */
class EntityStructureTest extends RestJsonApiTestCase
{
    private const ENTITY_ID = 'Oro_Bundle_NavigationBundle_Entity_MenuUpdate';

    public function testGetListReturnsMenuUpdateAsExcluded(): void
    {
        $response = $this->cget(['entity' => 'entitystructures']);

        $entityData = null;
        foreach (self::jsonToArray($response->getContent())['data'] as $item) {
            if ($item['id'] === self::ENTITY_ID) {
                $entityData = $item;
                break;
            }
        }

        self::assertNotNull($entityData, self::ENTITY_ID . ' is not found in the entity structures');
        self::assertArrayContains(
            ['attributes' => ['className' => MenuUpdate::class, 'options' => ['exclude' => true]]],
            $entityData
        );
    }

    public function testGetReturnsMenuUpdateAsExcluded(): void
    {
        $response = $this->get(['entity' => 'entitystructures', 'id' => self::ENTITY_ID]);

        self::assertArrayContains(
            ['data' => ['attributes' => ['className' => MenuUpdate::class, 'options' => ['exclude' => true]]]],
            self::jsonToArray($response->getContent())
        );
    }
}
