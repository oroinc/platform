<?php

namespace Oro\Bundle\EntityExtendBundle\Twig;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\EntityExtendEvents;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DynamicFieldsExtension extends AbstractDynamicFieldsExtension
{
    /** @var ConfigProvider|null */
    private $extendConfigProvider;

    /** @var ConfigProvider|null */
    private $entityConfigProvider;

    /** @var ConfigProvider|null */
    private $viewConfigProvider;

    /**
     * @return FieldTypeHelper
     */
    private function getFieldTypeHelper()
    {
        return $this->container->get('oro_entity_extend.extend.field_type_helper');
    }

    /**
     * @return ConfigProvider
     */
    private function getExtendConfigProvider()
    {
        if (null === $this->extendConfigProvider) {
            $this->extendConfigProvider = $this->container->get('oro_entity_config.provider.extend');
        }

        return $this->extendConfigProvider;
    }

    /**
     * @return ConfigProvider
     */
    private function getEntityConfigProvider()
    {
        if (null === $this->entityConfigProvider) {
            $this->entityConfigProvider = $this->container->get('oro_entity_config.provider.entity');
        }

        return $this->entityConfigProvider;
    }

    /**
     * @return ConfigProvider
     */
    private function getViewConfigProvider()
    {
        if (null === $this->viewConfigProvider) {
            $this->viewConfigProvider = $this->container->get('oro_entity_config.provider.view');
        }

        return $this->viewConfigProvider;
    }

    /**
     * @return PropertyAccessorInterface
     */
    private function getPropertyAccessor()
    {
        return $this->container->get('property_accessor');
    }

    /**
     * @return EventDispatcherInterface
     */
    private function getEventDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    private function getAuthorizationChecker()
    {
        return $this->container->get('security.authorization_checker');
    }

    /**
     * @return FeatureChecker
     */
    private function getFeatureChecker()
    {
        return $this->container->get('oro_featuretoggle.checker.feature_checker');
    }

    /**
     * {@inheritdoc}
     */
    public function getFields($entity, $entityClass = null)
    {
        if (null === $entityClass) {
            $entityClass = ClassUtils::getRealClass($entity);
        }

        $dynamicRows = [];
        $fields = $this->getExtendConfigProvider()->getConfigs($entityClass);
        foreach ($fields as $field) {
            if ($this->filterFields($field)) {
                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $field->getId();
                $fieldName = $fieldConfigId->getFieldName();
                $row = $this->createDynamicFieldRow($fieldConfigId, $fieldName, $entity);
                if ($row) {
                    $dynamicRows[$fieldName] = $row;
                }
            }
        }

        ArrayUtil::sortBy($dynamicRows, true);
        foreach ($dynamicRows as &$row) {
            unset($row['priority']);
        }

        return $dynamicRows;
    }

    /**
     * {@inheritdoc}
     */
    public function getField($entity, FieldConfigModel $field)
    {
        $fieldConfig = $this->getExtendConfigProvider()
            ->getConfig($field->getEntity()->getClassName(), $field->getFieldName());
        $row = $this->createDynamicFieldRow($fieldConfig->getId(), $field->getFieldName(), $entity);
        if ($row) {
            unset($row['priority']);
        }

        return $row;
    }

    /**
     * @param ConfigInterface $extendConfig
     *
     * @return bool
     */
    private function filterFields(ConfigInterface $extendConfig)
    {
        // skip system and not accessible fields
        if (!$extendConfig->is('owner', ExtendScope::OWNER_CUSTOM)
            || !ExtendHelper::isFieldAccessible($extendConfig)
        ) {
            return false;
        }

        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $extendConfig->getId();

        // skip invisible fields
        if (!$this->getViewConfigProvider()->getConfigById($fieldConfigId)->is('is_displayable')) {
            return false;
        }

        // skip relations if they are referenced to not accessible entity
        $underlyingFieldType = $this->getFieldTypeHelper()->getUnderlyingType($fieldConfigId->getFieldType());

        // skip disabled entities by feature flags
        if ($extendConfig->has('target_entity')
            && !$this->getFeatureChecker()->isResourceEnabled($extendConfig->get('target_entity'), 'entities')
        ) {
            return false;
        }

        return
            !in_array($underlyingFieldType, RelationType::$anyToAnyRelations, true)
            || ExtendHelper::isEntityAccessible(
                $this->getExtendConfigProvider()->getConfig($extendConfig->get('target_entity'))
            );
    }

    /**
     * @param FieldConfigId $fieldConfigId
     * @param string        $fieldName
     * @param object        $entity
     *
     * @return array
     */
    private function createDynamicFieldRow(FieldConfigId $fieldConfigId, $fieldName, $entity)
    {
        // Field ACL check
        if (!$this->getAuthorizationChecker()->isGranted('VIEW', new FieldVote($entity, $fieldName))) {
            return [];
        }

        $event = new ValueRenderEvent(
            $entity,
            $this->getPropertyAccessor()->getValue($entity, $fieldName),
            $fieldConfigId
        );
        $this->getEventDispatcher()->dispatch(EntityExtendEvents::BEFORE_VALUE_RENDER, $event);
        if (!$event->isFieldVisible()) {
            return [];
        }

        $fieldConfig = $this->getEntityConfigProvider()->getConfigById($fieldConfigId);
        $viewFieldConfig = $this->getViewConfigProvider()->getConfigById($fieldConfigId);

        return [
            'type'     => $viewFieldConfig->get('type') ?: $fieldConfigId->getFieldType(),
            'label'    => $fieldConfig->get('label') ?: $fieldName,
            'value'    => $event->getFieldViewValue(),
            'priority' => $viewFieldConfig->get('priority') ?: 0
        ];
    }
}
