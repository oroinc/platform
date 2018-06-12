<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Extend\Entity\TestApiE1 as ParentEntity;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\TestResourceInheritedFromEntity as Model;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * Tests configuration and metadata for API resource for a class inherited from ORM entity.
 */
class ModelInheritedFromEntityTest extends RestJsonApiTestCase
{
    /**
     * @return array
     */
    public function apiActionsProvider()
    {
        return [
            [ApiActions::GET],
            [ApiActions::CREATE],
            [ApiActions::UPDATE],
            [ApiActions::DELETE]
        ];
    }

    /**
     * @dataProvider apiActionsProvider
     */
    public function testConfigurationOfModelAndParentEntityShouldBeEqual($action)
    {
        $parentEntityConfig = $this->getApiConfig(ParentEntity::class, $action);
        $modelConfig = $this->getApiConfig(Model::class, $action);

        // the only difference between configuration of the model and its parent entity
        // is ParentResourceClass property
        self::assertEquals(ParentEntity::class, $modelConfig->getParentResourceClass());
        $modelConfig->setParentResourceClass(null);
        self::assertEquals($parentEntityConfig->toArray(), $modelConfig->toArray());
    }

    /**
     * @dataProvider apiActionsProvider
     */
    public function testMetadataOfModelAndParentEntityShouldBeEqual($action)
    {
        $parentEntityMetadata = $this->getApiMetadata(ParentEntity::class, $action);
        $modelMetadata = $this->getApiMetadata(Model::class, $action);

        // the only difference between metadata of the model and its parent entity
        // is ClassName property
        self::assertEquals(ParentEntity::class, $parentEntityMetadata->getClassName());
        self::assertEquals(Model::class, $modelMetadata->getClassName());
        $parentEntityMetadata->setClassName($modelMetadata->getClassName());
        self::assertEquals($parentEntityMetadata->toArray(), $modelMetadata->toArray());
    }
}
