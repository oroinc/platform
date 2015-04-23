<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;

class EntityContextProvider
{
    /**
     * @var ContainerInterface
     */
    protected $container;

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
     * @param ContainerInterface $container
     * @param EntityRoutingHelper $routingHelper
     * @param EntityProvider $entityProvider
     * @param ConfigProvider $entityConfigProvider
     */
    public function __construct(
        ContainerInterface $container,
        EntityRoutingHelper $routingHelper,
        EntityProvider $entityProvider,
        ConfigProvider $entityConfigProvider
    ) {
        $this->container = $container;
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
                    'first' => ($i == 0 ? true : false),
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
