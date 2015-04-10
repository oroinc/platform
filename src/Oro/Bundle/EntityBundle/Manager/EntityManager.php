<?php

namespace Oro\Bundle\EntityBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class EntityManager
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param EntityProvider $entityProvider
     * @param object $entity
     * @param string/null $filterByAlias
     * @return array
     */
    public function getSupportedTargets(EntityProvider $entityProvider, $entity, $filterByAlias = null)
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
                $alias = 'context-item-'.md5($targetEntity['name'].$i);
                $entityTargets[$alias] = [
                    'label' => $targetEntity['label'],
                    'name' => $targetEntity['name'],
                    'entityAlias' => $alias,
                    'first' => ($i == 1 ? true : false)
                ];

                if ($filterByAlias == $alias) {
                    return $entityTargets[$alias];
                }

                $i++;
            }
        }

        return $entityTargets;
    }

    /**
     * @param EntityProvider $entityProvider
     * @param ConfigProvider $entityConfigProvider
     * @param object $entity
     * @param string $entityAlias
     * @return string|null
     */
    public function getContextGridByEntity(
        EntityProvider $entityProvider,
        ConfigProvider $entityConfigProvider,
        $entity,
        $entityAlias
    ) {
        $entityTargets = $this->getSupportedTargets($entityProvider, $entity);

        if (!$entityAlias) {
            reset($entityTargets);
            $entityAlias = key($entityTargets);
        }

        if (isset($entityTargets[$entityAlias])) {
            $entityClass = $entityTargets[$entityAlias]['name'];
            $config = $entityConfigProvider->getConfig($entityClass);
            $gridName = $config->get('context-grid');
            if ($gridName) {
                return $gridName;
            }
        }

        return null;
    }
}
