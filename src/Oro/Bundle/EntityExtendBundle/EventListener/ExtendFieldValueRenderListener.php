<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\OptionSetRelation;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\OptionSetRelationRepository;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityConfigBundle\Tools\FieldAccessor;
use Oro\Bundle\FormBundle\Entity\PriorityItem;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;

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

    public function __construct(
        ConfigManager $configManager,
        UrlGeneratorInterface $router,
        FieldTypeHelper $fieldTypeHelper
    ) {
        $this->configManager = $configManager;
        $this->router = $router;
        $this->fieldTypeHelper = $fieldTypeHelper;

        $this->extendProvider = $configManager->getProvider('extend');
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function beforeValueRender(ValueRenderEvent $event)
    {
        $value = $event->getFieldValue();

        if (!$value) {
            return;
        }

        $type = $event->getFieldConfigId()->getFieldType();

        /** Prepare Relation field type */
        if ($value instanceof Collection) {
            $viewData = $this->getValueForCollection($value, $event->getFieldConfigId());
            $event->setFieldViewValue($viewData);

            return;
        }

        /** Prepare OptionSet field type */
        if ($type == 'optionSet') {
            $viewData = $this->getValueForOptionSet($event->getFieldValue(), $event->getFieldConfigId());
            $event->setFieldViewValue($viewData);

            return;
        }

        $underlyingFieldType = $this->fieldTypeHelper->getUnderlyingType($type);
        if ($underlyingFieldType == 'manyToOne') {
            $viewData = $this->propertyAccessor->getValue(
                $value,
                $this->extendProvider->getConfigById($event->getFieldConfigId())
                    ->get('target_field')
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
        $route       = false;
        $routeParams = false;
        if (class_exists($entityClassName)) {
            /** @var EntityMetadata $metadata */
            $metadata = $this->configManager->getEntityMetadata($entityClassName);
            if ($metadata && $metadata->routeView) {
                $route       = $metadata->routeView;
                $routeParams = [
                    'id' => null
                ];
            }

            $relationExtendConfig = $this->extendProvider->getConfig($entityClassName);
            if ($relationExtendConfig->is('owner', ExtendScope::OWNER_CUSTOM)) {
                $route       = self::ENTITY_VIEW_ROUTE;
                $routeParams = [
                    'entityName' => str_replace('\\', '_', $entityClassName),
                    'id'        => null
                ];
            }
        }

        return [
            'route'        => $route,
            'route_params' => $routeParams
        ];
    }

    /**
     * @param object $entity
     * @param FieldConfigId $fieldConfig
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
}
