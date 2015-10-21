<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\FormBundle\Entity\PriorityItem;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

class ExtendFieldValueRenderListener
{
    const ENTITY_VIEW_ROUTE = 'oro_entity_view';

    /**
     * @var ConfigProvider
     */
    protected $extendProvider;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var EntityClassNameHelper
     */
    private $entityClassNameHelper;

    /**
     * @param ConfigManager         $configManager
     * @param UrlGeneratorInterface $router
     * @param ManagerRegistry       $registry
     * @param SecurityFacade        $securityFacade
     * @param EntityClassNameHelper $entityClassNameHelper
     */
    public function __construct(
        ConfigManager $configManager,
        UrlGeneratorInterface $router,
        ManagerRegistry $registry,
        SecurityFacade $securityFacade,
        EntityClassNameHelper $entityClassNameHelper
    ) {
        $this->configManager = $configManager;
        $this->router = $router;
        $this->registry = $registry;
        $this->securityFacade = $securityFacade;
        $this->entityClassNameHelper = $entityClassNameHelper;

        $this->extendProvider = $configManager->getProvider('extend');
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function beforeValueRender(ValueRenderEvent $event)
    {
        $value = $event->getFieldValue();

        $type = $event->getFieldConfigId()->getFieldType();
        /** Prepare Relation field type */
        if ($value && $value instanceof Collection) {
            $viewData = $this->getValueForCollection($value, $event->getFieldConfigId());
            $event->setFieldViewValue($viewData);

            return;
        }

        if ($value && in_array($type, RelationType::$toOneRelations)) {
            $viewData = $this->getValueForSingleRelation(
                $value,
                $event->getFieldConfigId()
            );

            $event->setFieldViewValue($viewData);
        }
    }

    /**
     * @param Collection        $collection
     * @param ConfigIdInterface $fieldConfig
     *
     * @return array
     */
    protected function getValueForCollection(Collection $collection, ConfigIdInterface $fieldConfig)
    {
        $extendConfig   = $this->extendProvider->getConfigById($fieldConfig);
        $titleFieldName = $extendConfig->get('target_title');
        $targetClassName = $extendConfig->get('target_entity');

        if (!class_exists($targetClassName)) {
            return '';
        }

        $routeOptions = $this->getEntityRouteOptions($targetClassName);

        $values     = [];
        $priorities = [];
        /** @var object $item */
        foreach ($collection as $item) {
            $routeOptions['route_params']['id'] = $item->getId();

            $title = [];
            foreach ($titleFieldName as $fieldName) {
                $title[] = $this->propertyAccessor->getValue($item, $fieldName);
            }

            $value = [ 'id' => $item->getId(), 'title' => implode(' ', $title)];
            if (!empty($routeOptions['route']) && $this->securityFacade->isGranted('VIEW', $item)) {
                $value['link'] = $this->router->generate($routeOptions['route'], $routeOptions['route_params']);
            }
            $values[] = $value;

            if ($item instanceof PriorityItem) {
                $priorities[] = $item->getPriority();
            }
        }

        // sort values by priority if needed
        if (!empty($priorities) && count($priorities) === count($values)) {
            array_multisort($priorities, $values);
        }

        return ['values' => $values];
    }

    /**
     * Return view link options or simple text
     *
     * @param object          $targetEntity
     * @param ConfigIdInterface $fieldConfigId
     *
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @return array|string
     */
    protected function getValueForSingleRelation($targetEntity, ConfigIdInterface $fieldConfigId)
    {
        $fieldConfig = $this->extendProvider->getConfigById($fieldConfigId);

        $targetFieldName = $fieldConfig->get('target_field');
        $targetClassName = $fieldConfig->get('target_entity');

        if (!class_exists($targetClassName)) {
            return '';
        }

        $title = (string)$this->propertyAccessor->getValue($targetEntity, $targetFieldName);

        /** @var ClassMetadataInfo $targetMetadata */
        $targetMetadata = $this->registry
            ->getManager()
            ->getClassMetadata($targetClassName);
        $id = $this->propertyAccessor->getValue($targetEntity, $targetMetadata->getSingleIdentifierFieldName());

        $routeOptions = $this->getEntityRouteOptions($targetClassName, $id);
        if ($routeOptions['route'] && $this->securityFacade->isGranted('VIEW', $targetEntity)) {
            return [
                'link'  => $this->router->generate($routeOptions['route'], $routeOptions['route_params']),
                'title' => $title
            ];
        }

        return $title;
    }

    /**
     * @param string $entityClassName
     *
     * @param null   $id
     *
     * @return array
     */
    protected function getEntityRouteOptions($entityClassName, $id = null)
    {
        $relationExtendConfig = $this->extendProvider->getConfig($entityClassName);

        return $relationExtendConfig->is('owner', ExtendScope::OWNER_CUSTOM)
            ? $this->getCustomEntityViewRouteOptions($entityClassName, $id)
            : $this->getRegularEntityViewRouteOptions($entityClassName, $id);
    }

    /**
     * @param string $entityClassName
     * @param mixed  $id
     *
     * @return array
     */
    protected function getRegularEntityViewRouteOptions($entityClassName, $id = null)
    {
        $routeOptions = ['route' => false, 'route_params' => false];
        /** @var EntityMetadata $metadata */
        $metadata = $this->configManager->getEntityMetadata($entityClassName);
        if ($metadata && $metadata->routeView) {
            $routeOptions['route'] = $metadata->routeView;
            $routeOptions['route_params'] = ['id' => $id];
        }

        return $routeOptions;
    }

    /**
     * @param string   $entityClassName
     * @param mixed    $id
     *
     * @return array
     */
    protected function getCustomEntityViewRouteOptions($entityClassName, $id = null)
    {
        return [
            'route'        => self::ENTITY_VIEW_ROUTE,
            'route_params' => [
                'entityName' => $this->entityClassNameHelper->getUrlSafeClassName($entityClassName),
                'id'         => $id
            ]
        ];
    }
}
