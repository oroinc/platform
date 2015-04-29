<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;

class EntityContextProvider
{
    /**
     * @var EntityRoutingHelper
     */
    protected $routingHelper;

    /**
     * @var EntityProvider
     */
    protected $entityProvider;

    /**
     * @var ConfigProvider
     */
    protected $entityConfigProvider;

    /**
     * @param EntityRoutingHelper $routingHelper
     * @param EntityProvider $entityProvider
     * @param ConfigProvider $entityConfigProvider
     */
    public function __construct(
        EntityRoutingHelper $routingHelper,
        EntityProvider $entityProvider,
        ConfigProvider $entityConfigProvider
    ) {
        $this->routingHelper  = $routingHelper;
        $this->entityProvider = $entityProvider;
        $this->entityConfigProvider = $entityConfigProvider;
    }

    /**
     * @param object $entity
     * @return array
     */
    public function getSupportedTargets($entity)
    {
        $targetEntities = $this->entityProvider->getEntities();
        $entityTargets = [];

        if (!is_object($entity) || !method_exists($entity, 'supportActivityTarget')) {
            return $entityTargets;
        }

        $count = count($targetEntities);
        for ($i=0; $i < $count; $i++) {
            $targetEntity = $targetEntities[$i];
            $className = $targetEntity['name'];
            if (!empty($className) && $entity->supportActivityTarget($className)) {
                $entityTargets[] = [
                    'label' => $targetEntity['label'],
                    'className' => $this->routingHelper->encodeClassName($targetEntity['name']),
                    'first' => !(bool) $i,
                    'gridName' => $this->getContextGridByEntity($className)
                ];

                $i++;
            }
        }

        return $entityTargets;
    }

    /**
     * @param string $entityClass
     * @return string|null
     */
    public function getContextGridByEntity($entityClass)
    {
        if (!empty($entityClass)) {
            $entityClass = $this->routingHelper->decodeClassName($entityClass);
            $config = $this->entityConfigProvider->getConfig($entityClass);
            $gridName = $config->get('context-grid');
            if ($gridName) {
                return $gridName;
            }
        }

        return null;
    }
}
