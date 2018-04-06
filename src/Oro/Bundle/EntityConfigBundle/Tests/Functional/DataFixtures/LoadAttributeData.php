<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\Environment\AddAttributesToTestActivityTargetMigration;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\Environment\UpdateAttributesForTestActivityTargetMigration;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * @see \Oro\Bundle\EntityConfigBundle\Tests\Functional\Environment\TestEntitiesMigrationListener
 */
class LoadAttributeData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const ENTITY_CONFIG_MODEL       = TestActivityTarget::class;
    const SYSTEM_ATTRIBUTE_1        = AddAttributesToTestActivityTargetMigration::SYSTEM_ATTRIBUTE_1;
    const SYSTEM_ATTRIBUTE_2        = AddAttributesToTestActivityTargetMigration::SYSTEM_ATTRIBUTE_2;
    const DELETED_SYSTEM_ATTRIBUTE  = AddAttributesToTestActivityTargetMigration::DELETED_SYSTEM_ATTRIBUTE;
    const REGULAR_ATTRIBUTE_1       = AddAttributesToTestActivityTargetMigration::REGULAR_ATTRIBUTE_1;
    const REGULAR_ATTRIBUTE_2       = AddAttributesToTestActivityTargetMigration::REGULAR_ATTRIBUTE_2;
    const NOT_USED_ATTRIBUTE        = UpdateAttributesForTestActivityTargetMigration::NOT_USED_ATTRIBUTE;
    const DELETED_REGULAR_ATTRIBUTE = AddAttributesToTestActivityTargetMigration::DELETED_REGULAR_ATTRIBUTE;

    /**
     * @var array
     */
    private static $attributesData = [];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        if (!empty(self::$attributesData)) {
            return;
        }

        $attributes = [
            self::SYSTEM_ATTRIBUTE_1,
            self::SYSTEM_ATTRIBUTE_2,
            self::DELETED_SYSTEM_ATTRIBUTE,
            self::REGULAR_ATTRIBUTE_1,
            self::REGULAR_ATTRIBUTE_2,
            self::DELETED_REGULAR_ATTRIBUTE,
            self::NOT_USED_ATTRIBUTE,
        ];

        $configManager = $this->container->get('oro_entity_config.config_manager');
        foreach ($attributes as $attributeName) {
            $attribute = $configManager->getConfigFieldModel(self::ENTITY_CONFIG_MODEL, $attributeName);
            self::$attributesData[$attributeName] = $attribute->getId();
        }
    }

    /**
     * @param string $attributeName
     *
     * @return int|null
     */
    public static function getAttributeIdByName($attributeName)
    {
        return isset(self::$attributesData[$attributeName]) ? self::$attributesData[$attributeName] : null;
    }
}
