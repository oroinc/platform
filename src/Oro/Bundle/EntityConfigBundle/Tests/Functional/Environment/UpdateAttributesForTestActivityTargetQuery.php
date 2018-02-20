<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Environment;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Psr\Log\LoggerInterface;

/**
 * Marks "deleted_system_attribute" and "deleted_regular_attribute" extended attributes
 * of TestActivityTarget entity as to be deleted
 * and adds "not_used_attribute" attribute for this entity with "new" state.
 * @see \Oro\Bundle\EntityConfigBundle\Tests\Functional\Environment\AddAttributesToTestActivityTargetMigration
 * @see \Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope::STATE_DELETE
 * @see \Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope::STATE_NEW
 */
class UpdateAttributesForTestActivityTargetQuery implements MigrationQuery
{
    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->markAttributeAsToBeDeleted(AddAttributesToTestActivityTargetMigration::DELETED_SYSTEM_ATTRIBUTE);
        $this->markAttributeAsToBeDeleted(AddAttributesToTestActivityTargetMigration::DELETED_REGULAR_ATTRIBUTE);
        $this->addAttribute(UpdateAttributesForTestActivityTargetMigration::NOT_USED_ATTRIBUTE);

        $this->configManager->flush();
    }

    /**
     * @param string $attributeName
     */
    private function markAttributeAsToBeDeleted($attributeName)
    {
        $entityClass = TestActivityTarget::class;

        $extendConfig = $this->configManager->getFieldConfig('extend', $entityClass, $attributeName);
        $extendConfig->set('state', ExtendScope::STATE_DELETE);
        $this->configManager->persist($extendConfig);
    }

    /**
     * @param string $attributeName
     */
    private function addAttribute($attributeName)
    {
        $entityClass = TestActivityTarget::class;

        $this->configManager->createConfigFieldModel($entityClass, $attributeName, 'string');

        $extendConfig = $this->configManager->getFieldConfig('extend', $entityClass, $attributeName);
        $extendConfig->set('owner', ExtendScope::OWNER_CUSTOM);
        $extendConfig->set('state', ExtendScope::STATE_NEW);
        $this->configManager->persist($extendConfig);

        $attributeConfig = $this->configManager->getFieldConfig('attribute', $entityClass, $attributeName);
        $attributeConfig->set('is_attribute', true);
        $this->configManager->persist($attributeConfig);
    }
}
