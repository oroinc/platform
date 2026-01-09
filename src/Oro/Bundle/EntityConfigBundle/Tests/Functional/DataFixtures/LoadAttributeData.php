<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\Environment\AddAttributesToTestActivityTargetMigration;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * @see \Oro\Bundle\EntityConfigBundle\Tests\Functional\Environment\TestEntitiesMigrationListener
 */
class LoadAttributeData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const ENTITY_CONFIG_MODEL = TestActivityTarget::class;
    public const SYSTEM_ATTRIBUTE_1  = AddAttributesToTestActivityTargetMigration::SYSTEM_ATTRIBUTE_1;
    public const SYSTEM_ATTRIBUTE_2  = AddAttributesToTestActivityTargetMigration::SYSTEM_ATTRIBUTE_2;
    public const REGULAR_ATTRIBUTE_1 = AddAttributesToTestActivityTargetMigration::REGULAR_ATTRIBUTE_1;
    public const REGULAR_ATTRIBUTE_2 = AddAttributesToTestActivityTargetMigration::REGULAR_ATTRIBUTE_2;
    public const BOOL_ATTRIBUTE_1 = AddAttributesToTestActivityTargetMigration::BOOL_ATTRIBUTE_1;

    /** @var array */
    private static $attributesData = [];

    #[\Override]
    public function load(ObjectManager $manager)
    {
        if (!empty(self::$attributesData)) {
            return;
        }

        $attributes = [
            self::SYSTEM_ATTRIBUTE_1,
            self::SYSTEM_ATTRIBUTE_2,
            self::REGULAR_ATTRIBUTE_1,
            self::REGULAR_ATTRIBUTE_2,
            self::BOOL_ATTRIBUTE_1,
        ];

        $configManager = $this->container->get('oro_entity_config.config_manager');
        foreach ($attributes as $attributeName) {
            self::$attributesData[$attributeName] = self::getAttribute($configManager, $attributeName)->getId();
        }
    }

    public static function getAttributeIdByName(string $attributeName): ?int
    {
        return self::$attributesData[$attributeName] ?? null;
    }

    public static function getAttribute(ConfigManager $configManager, string $attributeName): FieldConfigModel
    {
        $attribute = $configManager->getConfigFieldModel(self::ENTITY_CONFIG_MODEL, $attributeName);
        if (null === $attribute) {
            throw new \RuntimeException(
                sprintf('The attribute "%s::%s" not found.', self::ENTITY_CONFIG_MODEL, $attributeName)
            );
        }

        return $attribute;
    }
}
