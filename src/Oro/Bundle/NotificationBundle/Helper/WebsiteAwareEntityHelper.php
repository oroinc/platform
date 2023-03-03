<?php

namespace Oro\Bundle\NotificationBundle\Helper;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Website aware entity helper.
 */
class WebsiteAwareEntityHelper
{
    public function __construct(protected ConfigManager $configProvider)
    {
    }

    public function isWebsiteAware(object|string $objectOrClass): bool
    {
        $className = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;

        return $this->getWebsiteAwareInfo($className, 'is_website_aware');
    }

    protected function getWebsiteAwareInfo(string $className, string $optionName): bool
    {
        $className = ClassUtils::getRealClass($className);
        if ($this->configProvider->hasConfig($className)) {
            /** @var Config $websiteAwareInfo */
            $websiteAwareInfo = $this->configProvider->getEntityConfig('website', $className);
            $websiteValues = $websiteAwareInfo->getValues();
            if (isset($websiteValues[$optionName])) {
                return $websiteValues[$optionName];
            }
        }

        return false;
    }
}
