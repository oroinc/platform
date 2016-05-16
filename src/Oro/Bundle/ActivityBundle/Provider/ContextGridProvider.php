<?php

namespace Oro\Bundle\ActivityBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;

class ContextGridProvider
{
    /** @var EntityRoutingHelper */
    protected $routingHelper;

    /** @var EntityProvider */
    protected $entityProvider;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /**
     * @param EntityRoutingHelper $routingHelper
     * @param EntityProvider      $entityProvider
     * @param ConfigProvider      $entityConfigProvider
     */
    public function __construct(
        EntityRoutingHelper $routingHelper,
        EntityProvider $entityProvider,
        ConfigProvider $entityConfigProvider
    ) {
        $this->routingHelper        = $routingHelper;
        $this->entityProvider       = $entityProvider;
        $this->entityConfigProvider = $entityConfigProvider;
    }

    /**
     * @param object            $entity
     * @param SecurityFacade    $securityFacade
     *
     * @return array
     */
    public function getSupportedTargets($entity, $securityFacade)
    {
        $targetEntities = $this->entityProvider->getEntities();
        $entityTargets  = [];

        if (!is_object($entity) || !method_exists($entity, 'supportActivityTarget')) {
            return $entityTargets;
        }

        $count = count($targetEntities);
        for ($i = 0; $i < $count; $i++) {

            if (!$securityFacade->isGranted('VIEW', 'entity:'.$targetEntities[$i]['name'])) {
                continue;
            }

            $targetEntity = $targetEntities[$i];
            $className    = $targetEntity['name'];
            $gridName     = $this->getContextGridByEntity($className);
            if ($gridName && !empty($className) && $entity->supportActivityTarget($className)) {
                $entityTargets[] = [
                    'label'     => $targetEntity['label'],
                    'className' => $this->routingHelper->getUrlSafeClassName($targetEntity['name']),
                    'first'     => count($entityTargets) === 0,
                    'gridName'  => $gridName
                ];
            }
        }

        return $entityTargets;
    }

    /**
     * @param string $entityClass
     *
     * @return string|null
     */
    public function getContextGridByEntity($entityClass)
    {
        if (!empty($entityClass)) {
            $entityClass = $this->routingHelper->resolveEntityClass($entityClass);
            if (ExtendHelper::isCustomEntity($entityClass)) {
                return 'custom-entity-grid';
            }
            $config = $this->entityConfigProvider->getConfig($entityClass);
            if ($config->has('context')) {
                return $config->get('context');
            }
            if ($config->has('default')) {
                return $config->get('default');
            }
        }

        return null;
    }
}
