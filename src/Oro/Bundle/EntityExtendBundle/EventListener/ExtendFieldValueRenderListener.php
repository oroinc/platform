<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\OptionSetRelation;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\OptionSetRelationRepository;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\FormBundle\Entity\PriorityItem;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

class ExtendFieldValueRenderListener
{
    const ENTITY_VIEW_ROUTE = 'oro_entity_view';

    /**
     * @var ConfigProviderInterface
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
     * @var FieldTypeHelper
     */
    protected $fieldTypeHelper;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param ConfigManager         $configManager
     * @param UrlGeneratorInterface $router
     * @param FieldTypeHelper       $fieldTypeHelper
     * @param EntityManager         $entityManager
     */
    public function __construct(
        ConfigManager $configManager,
        UrlGeneratorInterface $router,
        FieldTypeHelper $fieldTypeHelper,
        EntityManager $entityManager
    ) {
        $this->configManager = $configManager;
        $this->router = $router;
        $this->fieldTypeHelper = $fieldTypeHelper;
        $this->entityManager = $entityManager;

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

        /** Prepare OptionSet field type */
        if ($type == 'optionSet') {
            $viewData = $this->getValueForOptionSet($event->getEntity(), $event->getFieldConfigId());
            $event->setFieldViewValue($viewData);

            return;
        }

        $underlyingFieldType = $this->fieldTypeHelper->getUnderlyingType($type);
        if ($value && $underlyingFieldType === RelationType::MANY_TO_ONE) {
            $viewData = $this->getValueForManyToOne(
                $value,
                $this->extendProvider->getConfigById($event->getFieldConfigId())
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

        $value = $this->getEntityRouteOptions($extendConfig->get('target_entity'));

        $values     = [];
        $priorities = [];
        /** @var object $item */
        foreach ($collection as $item) {
            $value['route_params']['id'] = $item->getId();

            $title = [];
            foreach ($titleFieldName as $fieldName) {
                $title[] = $this->propertyAccessor->getValue($item, $fieldName);
            }

            $values[] = [
                'id'    => $item->getId(),
                'link'  => $value['route'] ? $this->router->generate($value['route'], $value['route_params']) : false,
                'title' => implode(' ', $title)
            ];
            if ($item instanceof PriorityItem) {
                $priorities[] = $item->getPriority();
            }
        }

        // sort values by priority if needed
        if (!empty($priorities) && count($priorities) === count($values)) {
            array_multisort($priorities, $values);
        }

        $value['values'] = $values;

        return $value;
    }

    /**
     * @param string $entityClassName
     *
     * @return array
     */
    protected function getEntityRouteOptions($entityClassName)
    {
        if (class_exists($entityClassName)) {
            $relationExtendConfig = $this->extendProvider->getConfig($entityClassName);

            return $relationExtendConfig->is('owner', ExtendScope::OWNER_CUSTOM)
                ? $this->getCustomEntityViewRouteOptions($entityClassName)
                : $this->getClassViewRouteOptions($entityClassName);
        }

        return [
            'route'        => false,
            'route_params' => false
        ];
    }

    /**
     * @param object $entity
     * @param FieldConfigId $fieldConfig
     *
     * @return OptionSetRelation[]
     */
    protected function getValueForOptionSet($entity, FieldConfigId $fieldConfig)
    {
        /** @var $optionSetRepository OptionSetRelationRepository */
        $optionSetRepository = $this->configManager
            ->getEntityManager()
            ->getRepository(OptionSetRelation::ENTITY_NAME);

        $model = $this->configManager->getConfigFieldModel(
            $fieldConfig->getClassName(),
            $fieldConfig->getFieldName()
        );

        $value = $optionSetRepository->findByFieldId($model->getId(), $entity->getId());
        array_walk(
            $value,
            function (OptionSetRelation &$item) {
                $item = array('title' => $item->getOption()->getLabel());
            }
        );

        $value['values'] = $value;

        return $value;
    }

    /**
     * Return view link options or simple text
     *
     * @param object          $targetEntity
     * @param ConfigInterface $field
     *
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @return array|string
     */
    protected function getValueForManyToOne($targetEntity, ConfigInterface $field)
    {
        $targetFieldName = $field->get('target_field');
        $targetClassName = $field->get('target_entity');

        if (!class_exists($targetClassName)) {
            return '';
        }

        $title = (string)$this->propertyAccessor->getValue(
            $targetEntity,
            $targetFieldName
        );

        $targetMetadata = $this->entityManager->getClassMetadata($targetClassName);
        $id = $this->propertyAccessor->getValue(
            $targetEntity,
            $targetMetadata->getSingleIdentifierFieldName()
        );

        $relationExtendConfig = $this->extendProvider->getConfig($targetClassName);
        $routeOptions = $relationExtendConfig->is('owner', ExtendScope::OWNER_CUSTOM)
            ? $this->getCustomEntityViewRouteOptions($targetClassName, $id)
            : $this->getClassViewRouteOptions($targetClassName, $id);
        if ($routeOptions['route']) {
            return [
                'link'  => $this->router->generate($routeOptions['route'], $routeOptions['route_params']),
                'title' => $title
            ];
        }

        return $title;
    }

    /**
     * @param string $entityClassName
     * @param mixed  $id
     *
     * @return array
     */
    protected function getClassViewRouteOptions($entityClassName, $id = null)
    {
        $routeOptions = ['route' => false, 'route_params' => false];
        /** @var EntityMetadata $metadata */
        $metadata = $this->configManager->getEntityMetadata($entityClassName);
        if ($metadata && $metadata->routeView) {
            $routeOptions['route'] = $metadata->routeView;
            $routeOptions['route_params'] = [
                'id' => $id
            ];
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
                'entityName' => str_replace('\\', '_', $entityClassName),
                'id'         => $id
            ]
        ];
    }
}
