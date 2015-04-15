<?php

namespace Oro\Bundle\EntityBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;

class EntityManager
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /** @var EntityRoutingHelper */
    protected $routingHelper;

    /**
     * @param ContainerInterface $container
     * @param EntityRoutingHelper $routingHelper
     */
    public function __construct(ContainerInterface $container, EntityRoutingHelper $routingHelper)
    {
        $this->container = $container;
        $this->routingHelper  = $routingHelper;
    }

    /**
     * @param EntityProvider $entityProvider
     * @param ConfigProvider $entityConfigProvider
     * @param object $entity
     * @return array
     */
    public function getSupportedTargets(EntityProvider $entityProvider, ConfigProvider $entityConfigProvider, $entity)
    {
        $targetEntities = $entityProvider->getEntities();
        $entityTargets = [];

        if (!is_object($entity) || !method_exists($entity, 'supportActivityTarget')) {
            return $entityTargets;
        }

        $i=1;
        foreach ($targetEntities as $targetEntity) {
            $className = $targetEntity['name'];
            if (!empty($className) && $entity->supportActivityTarget($className)) {
                $entityTargets[] = [
                    'label' => $targetEntity['label'],
                    'className' => $this->routingHelper->encodeClassName($targetEntity['name']),
                    'first' => ($i == 1 ? true : false),
                    'gridName' => $this->getContextGridByEntity($entityConfigProvider, $className)
                ];

                $i++;
            }
        }

        return $entityTargets;
    }

    /**
     * @param ConfigProvider $entityConfigProvider
     * @param string $entityClass
     * @return string|null
     */
    public function getContextGridByEntity(
        ConfigProvider $entityConfigProvider,
        $entityClass
    ) {
        if (!empty($entityClass)) {
            $entityClass = $this->routingHelper->decodeClassName($entityClass);
            $config = $entityConfigProvider->getConfig($entityClass);
            $gridName = $config->get('context-grid');
            if ($gridName) {
                return $gridName;
            }
        }

        return null;
    }
}
