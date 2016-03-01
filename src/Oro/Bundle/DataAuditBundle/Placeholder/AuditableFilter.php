<?php

namespace Oro\Bundle\DataAuditBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class AuditableFilter
{
    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @param mixed $entity
     * @param bool  $show
     *
     * @return bool
     */
    public function isEntityAuditable($entity, $show)
    {
        if ($show || !is_object($entity)) {
            return $show;
        }

        $className = ClassUtils::getClass($entity);

        return
            $this->configProvider->hasConfig($className)
            && $this->configProvider->getConfig($className)->is('auditable');
    }
}
