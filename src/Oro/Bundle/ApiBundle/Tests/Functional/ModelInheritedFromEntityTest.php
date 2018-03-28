<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Extend\Entity\TestApiE1 as ParentEntity;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\TestResourceInheritedFromEntity as Model;

/**
 * Tests configuration and metadata for API resource for a class inherited from ORM entity.
 */
class ModelInheritedFromEntityTest extends RestJsonApiTestCase
{
    /**
     * @param string $entityClass
     * @param string $action
     *
     * @return EntityDefinitionConfig
     */
    private function getConfig(string $entityClass, string $action): EntityDefinitionConfig
    {
        /** @var ConfigProvider $configProvider */
        $configProvider = self::getContainer()->get('oro_api.config_provider');

        $config = $configProvider->getConfig(
            $entityClass,
            Version::LATEST,
            $this->getRequestType(),
            [
                new EntityDefinitionConfigExtra($action)
            ]
        );

        return $config->getDefinition();
    }

    /**
     * @param string $entityClass
     * @param string $action
     *
     * @return EntityMetadata
     */
    private function getMetadata(string $entityClass, string $action): EntityMetadata
    {
        /** @var MetadataProvider $metadataProvider */
        $metadataProvider = self::getContainer()->get('oro_api.metadata_provider');

        return $metadataProvider->getMetadata(
            $entityClass,
            Version::LATEST,
            $this->getRequestType(),
            $this->getConfig($entityClass, $action),
            [
                new ActionMetadataExtra($action)
            ]
        );
    }

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
        $parentEntityConfig = $this->getConfig(ParentEntity::class, $action);
        $modelConfig = $this->getConfig(Model::class, $action);

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
        $parentEntityMetadata = $this->getMetadata(ParentEntity::class, $action);
        $modelMetadata = $this->getMetadata(Model::class, $action);

        // the only difference between metadata of the model and its parent entity
        // is ClassName property
        self::assertEquals(ParentEntity::class, $parentEntityMetadata->getClassName());
        self::assertEquals(Model::class, $modelMetadata->getClassName());
        $parentEntityMetadata->setClassName($modelMetadata->getClassName());
        self::assertEquals($parentEntityMetadata->toArray(), $modelMetadata->toArray());
    }
}
