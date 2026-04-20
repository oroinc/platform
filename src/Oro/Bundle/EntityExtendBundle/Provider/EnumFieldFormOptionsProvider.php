<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Returns form options for enum fields
 */
class EnumFieldFormOptionsProvider implements ExtendFieldFormOptionsProviderInterface
{
    private EntityConfigManager $entityConfigManager;

    public function __construct(EntityConfigManager $entityConfigManager)
    {
        $this->entityConfigManager = $entityConfigManager;
    }

    #[\Override]
    public function getOptions(string $className, string $fieldName): array
    {
        $className = ClassUtils::getRealClass($className);

        $entityFieldConfig = $this->entityConfigManager->getFieldConfig('entity', $className, $fieldName);
        $enumFieldConfig = $this->entityConfigManager->getFieldConfig('enum', $className, $fieldName);

        return [
            'label' => $entityFieldConfig->get('label'),
            'block' => 'general',
            'enum_code' => $enumFieldConfig->get('enum_code'),
            'multiple' => ExtendHelper::isMultiEnumType($enumFieldConfig->getId()->getFieldType()),
        ];
    }
}
