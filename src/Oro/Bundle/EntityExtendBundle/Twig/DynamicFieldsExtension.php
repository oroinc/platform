<?php

namespace Oro\Bundle\EntityExtendBundle\Twig;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Component\PhpUtils\ArrayUtil;

use Oro\Bundle\EntityExtendBundle\EntityExtendEvents;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

class DynamicFieldsExtension extends \Twig_Extension
{
    const NAME = 'oro_entity_config_fields';

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    /** @var ConfigProvider */
    protected $extendProvider;

    /** @var ConfigProvider */
    protected $entityProvider;

    /** @var ConfigProvider */
    protected $viewProvider;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param ConfigManager            $configManager
     * @param FieldTypeHelper          $fieldTypeHelper
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        ConfigManager $configManager,
        FieldTypeHelper $fieldTypeHelper,
        EventDispatcherInterface $dispatcher
    ) {
        $this->fieldTypeHelper  = $fieldTypeHelper;
        $this->eventDispatcher  = $dispatcher;
        $this->viewProvider     = $configManager->getProvider('view');
        $this->extendProvider   = $configManager->getProvider('extend');
        $this->entityProvider   = $configManager->getProvider('entity');
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_get_dynamic_fields', [$this, 'getFields']),
        ];
    }

    /**
     * @param object      $entity
     * @param null|string $entityClass
     * @return array
     */
    public function getFields($entity, $entityClass = null)
    {
        $dynamicRow = [];

        if (null === $entityClass) {
            $entityClass = ClassUtils::getRealClass($entity);
        }

        $fields = $this->extendProvider->filter([$this, 'filterFields'], $entityClass);
        foreach ($fields as $field) {
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $field->getId();

            $fieldName = $fieldConfigId->getFieldName();
            $fieldType = $fieldConfigId->getFieldType();

            $value = $this->propertyAccessor->getValue($entity, $fieldName);

            $event = new ValueRenderEvent($entity, $value, $fieldConfigId);
            $this->eventDispatcher->dispatch(
                EntityExtendEvents::BEFORE_VALUE_RENDER,
                $event
            );

            if (!$event->isFieldVisible()) {
                continue;
            }

            $fieldConfig = $this->entityProvider->getConfigById($fieldConfigId);
            $dynamicRow[$fieldName] = [
                'type'     => $this->viewProvider->getConfigById($fieldConfigId)->get('type') ?: $fieldType,
                'label'    => $fieldConfig->get('label') ?: $fieldName,
                'value'    => $event->getFieldViewValue(),
                'priority' => $this->viewProvider->getConfigById($fieldConfigId)->get('priority') ?: 0
            ];
        }

        ArrayUtil::sortBy($dynamicRow, true);
        foreach ($dynamicRow as &$row) {
            unset($row['priority']);
        }

        return $dynamicRow;
    }

    /**
     * @param ConfigInterface $config
     * @return bool
     */
    public function filterFields(ConfigInterface $config)
    {
        $extendConfig = $this->extendProvider->getConfigById($config->getId());
        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $extendConfig->getId();

        // skip system and not accessible fields
        if (!$config->is('owner', ExtendScope::OWNER_CUSTOM)
            || !ExtendHelper::isFieldAccessible($config)
        ) {
            return false;
        }

        // skip invisible fields
        if (!$this->viewProvider->getConfigById($config->getId())->is('is_displayable')) {
            return false;
        }

        // skip relations if they are referenced to not accessible entity
        $underlyingFieldType = $this->fieldTypeHelper->getUnderlyingType($fieldConfigId->getFieldType());
        if (in_array($underlyingFieldType, RelationType::$anyToAnyRelations, true)
            && !ExtendHelper::isEntityAccessible(
                $this->extendProvider->getConfig($extendConfig->get('target_entity'))
            )
        ) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
