<?php

namespace Oro\Bundle\DataAuditBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class AuditableFilter
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @param mixed  $entity
     * @param string $entityClass
     * @param bool   $show
     * @return bool
     */
    public function isEntityAuditable($entity, $entityClass, $show)
    {
        if (!is_object($entity) || $show) {
            return $show;
        }

        $classEmpty = empty($entityClass);

        if ($classEmpty && $entity instanceof EntityConfigModel) {
            $className = $entity->getClassName();
        } elseif ($classEmpty) {
            $className = ClassUtils::getClass($entity);
        } else {
            $className = str_replace('_', '\\', $entityClass);
        }

        return $this->configProvider->hasConfig($className)
        && $this->configProvider->getConfig($className)->is('auditable');
    }
}
