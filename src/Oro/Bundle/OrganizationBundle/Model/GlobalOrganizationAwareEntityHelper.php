<?php

namespace Oro\Bundle\OrganizationBundle\Model;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Organization aware entity helper.
 */
class GlobalOrganizationAwareEntityHelper
{
    public function __construct(protected ConfigManager $configProvider)
    {
    }

    public function isGlobalOrganizationAware(object|string $objectOrClass): bool
    {
        $className = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;

        return $this->getGlobalOrganizationAware($className, 'is_global_aware');
    }

    protected function getGlobalOrganizationAware(string $className, string $optionName): bool
    {
        $className = ClassUtils::getRealClass($className);
        if ($this->configProvider->hasConfig($className)) {
            /** @var Config $globalOrganizationInfo */
            $globalOrganizationInfo = $this->configProvider->getEntityConfig('global_organization', $className);
            $globalOrganizationValues = $globalOrganizationInfo->getValues();
            if (isset($globalOrganizationValues[$optionName])) {
                return $globalOrganizationValues[$optionName];
            }
        }

        return false;
    }
}
