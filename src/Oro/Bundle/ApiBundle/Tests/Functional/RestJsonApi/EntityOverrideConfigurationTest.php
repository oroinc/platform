<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOverrideClassOwner;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\TestCurrentDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\TestOverrideClassOwnerModel;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * Tests configuration and metadata for API resource for a class inherited from ORM entity.
 */
class EntityOverrideConfigurationTest extends RestJsonApiTestCase
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

    public function testResolveEntityClassByTypeForEntityThatOverrideAnotherEntity()
    {
        $entityClass = $this->getEntityClass('testapioverrideclassowners');
        self::assertEquals(TestOverrideClassOwnerModel::class, $entityClass);
    }

    public function testResolveEntityTypeByClassForEntityThatOverrideAnotherEntity()
    {
        $entityType = $this->getEntityType(TestOverrideClassOwnerModel::class);
        self::assertEquals('testapioverrideclassowners', $entityType);
    }

    public function testResolveEntityTypeByClassForEntityThatIsOverriddenByAnotherEntity()
    {
        $entityType = $this->getEntityType(TestOverrideClassOwner::class);
        self::assertEquals('testapioverrideclassowners', $entityType);
    }

    /**
     * @dataProvider apiActionsProvider
     */
    public function testConfigurationOfOverrideModelAndOverriddenEntityShouldBeEqual($action)
    {
        $overriddenEntityConfig = $this->getConfig(TestOverrideClassOwner::class, $action);
        $overrideModelConfig = $this->getConfig(TestOverrideClassOwnerModel::class, $action);

        // the only difference between configuration of the model and its parent entity
        // is ParentResourceClass property
        self::assertEquals(TestOverrideClassOwner::class, $overrideModelConfig->getParentResourceClass());
        $overrideModelConfig->setParentResourceClass(null);
        self::assertEquals($overriddenEntityConfig->toArray(), $overrideModelConfig->toArray());
    }

    /**
     * @dataProvider apiActionsProvider
     */
    public function testMetadataOfOverrideModelAndOverriddenEntityShouldBeEqual($action)
    {
        $overriddenEntityMetadata = $this->getMetadata(TestOverrideClassOwner::class, $action);
        $overrideModelMetadata = $this->getMetadata(TestOverrideClassOwnerModel::class, $action);

        // the only difference between metadata of the model and its parent entity
        // is ClassName property
        self::assertEquals(TestOverrideClassOwner::class, $overriddenEntityMetadata->getClassName());
        self::assertEquals(TestOverrideClassOwnerModel::class, $overrideModelMetadata->getClassName());
        $overriddenEntityMetadata->setClassName($overrideModelMetadata->getClassName());
        self::assertEquals($overriddenEntityMetadata->toArray(), $overrideModelMetadata->toArray());
    }

    public function testResolveEntityClassByTypeForEntityThatHasModelInheritedFromIt()
    {
        $entityClass = $this->getEntityClass('testapidepartments');
        self::assertEquals(TestDepartment::class, $entityClass);
    }

    public function testResolveEntityTypeByClassForEntityThatHasModelInheritedFromIt()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        self::assertEquals('testapidepartments', $entityType);
    }

    public function testResolveEntityClassByTypeForModelInheritedFromEntity()
    {
        $entityClass = $this->getEntityClass('testapicurrentdepartments');
        self::assertEquals(TestCurrentDepartment::class, $entityClass);
    }

    public function testResolveEntityTypeByClassForModelInheritedFromEntity()
    {
        $entityType = $this->getEntityType(TestCurrentDepartment::class);
        self::assertEquals('testapicurrentdepartments', $entityType);
    }

    /**
     * @dataProvider apiActionsProvider
     */
    public function testConfigurationForModelInheritedFromEntity($action)
    {
        $parentEntityConfig = $this->getConfig(TestDepartment::class, $action);
        $modelConfig = $this->getConfig(TestCurrentDepartment::class, $action);

        // the only difference between configuration of the model and its parent entity
        // is ParentResourceClass property
        self::assertEquals(TestDepartment::class, $modelConfig->getParentResourceClass());
        $modelConfig->setParentResourceClass(null);
        self::assertEquals($parentEntityConfig->toArray(), $modelConfig->toArray());
    }

    /**
     * @dataProvider apiActionsProvider
     */
    public function testMetadataForModelInheritedFromEntity($action)
    {
        $parentEntityMetadata = $this->getMetadata(TestDepartment::class, $action);
        $modelMetadata = $this->getMetadata(TestCurrentDepartment::class, $action);

        // the only difference between metadata of the model and its parent entity
        // is ClassName property
        self::assertEquals(TestDepartment::class, $parentEntityMetadata->getClassName());
        self::assertEquals(TestCurrentDepartment::class, $modelMetadata->getClassName());
        $parentEntityMetadata->setClassName($modelMetadata->getClassName());
        self::assertEquals($parentEntityMetadata->toArray(), $modelMetadata->toArray());
    }

    /**
     * @dataProvider apiActionsProvider
     */
    public function testConfigurationForEntityWithAssociationToEntityThatHasModelInheritedFromIt($action)
    {
        $entityConfig = $this->getConfig(TestEmployee::class, $action);

        // the association target class should be an entity, not a model
        self::assertEquals(TestDepartment::class, $entityConfig->getField('department')->getTargetClass());
    }

    /**
     * @dataProvider apiActionsProvider
     */
    public function testMetadataForEntityWithAssociationToEntityThatHasModelInheritedFromIt($action)
    {
        $entityMetadata = $this->getMetadata(TestEmployee::class, $action);

        // the association target class should be an entity, not a model
        self::assertEquals(TestDepartment::class, $entityMetadata->getAssociation('department')->getTargetClassName());
    }
}
