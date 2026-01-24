<?php

namespace Oro\Bundle\DataAuditBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * Placeholder filter that determines whether an entity is auditable.
 *
 * This filter is used in placeholder templates to conditionally display audit-related UI elements
 * (such as change history links or audit widgets) based on whether the entity has audit tracking
 * enabled. It checks the entity's configuration to determine if the `auditable` flag is set,
 * allowing the system to show or hide audit functionality dynamically based on entity configuration.
 */
class AuditableFilter
{
    /** @var ConfigProvider */
    protected $configProvider;

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
