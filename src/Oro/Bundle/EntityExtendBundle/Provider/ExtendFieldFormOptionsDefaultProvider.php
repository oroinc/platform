<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityExtend\CachedClassUtils;

/**
 * Returns default form options.
 */
class ExtendFieldFormOptionsDefaultProvider implements ExtendFieldFormOptionsProviderInterface
{
    private EntityConfigManager $entityConfigManager;

    public function __construct(EntityConfigManager $entityConfigManager)
    {
        $this->entityConfigManager = $entityConfigManager;
    }

    public function getOptions(string $className, string $fieldName): array
    {
        $className = CachedClassUtils::getRealClass($className);
        $entityFieldConfig = $this->entityConfigManager->getFieldConfig('entity', $className, $fieldName);

        return [
            'label' => $entityFieldConfig->get('label'),
            'required' => false,
            'block' => 'general',
        ];
    }
}
