<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Util;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Tools\FieldAccessor;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DynamicFieldsHelper
{
    /** @var ConfigManager */
    private $configManager;

    /** @var FeatureChecker */
    private $featureChecker;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var RouterInterface */
    private $router;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        ConfigManager $configManager,
        FeatureChecker $featureChecker,
        DoctrineHelper $doctrineHelper,
        RouterInterface $router,
        TranslatorInterface $translator
    ) {
        $this->configManager = $configManager;
        $this->featureChecker = $featureChecker;
        $this->doctrineHelper = $doctrineHelper;
        $this->router = $router;
        $this->translator = $translator;
        $this->propertyAccessor = new PropertyAccessor();
    }

    /**
     * @param string $className
     * @param ConfigInterface $formConfig
     * @param FormView $view
     * @param bool|null $extraField set value for extra_field. Leave null, if it shouldn't be change
     * @return bool
     */
    public function shouldBeInitialized(
        $className,
        ConfigInterface $formConfig,
        FormView $view,
        bool $extraField = null
    ) {
        $extendConfigProvider = $this->configManager->getProvider('extend');

        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $formConfig->getId();
        $fieldName     = $fieldConfigId->getFieldName();

        $extendConfig = $extendConfigProvider->getConfig($className, $fieldName);
        if (!$this->isApplicableField($extendConfig, $extendConfigProvider)) {
            return false;
        }

        // check if a field exists, because it is possible that it can be removed by some event listener
        if (!isset($view->children[$fieldName])) {
            return false;
        }

        if ($extraField !== null) {
            $view->children[$fieldName]->vars['extra_field'] = $extraField;
        }

        if (!in_array($fieldConfigId->getFieldType(), RelationType::$toManyRelations, true)) {
            return false;
        }

        return true;
    }

    /**
     * @param ConfigInterface $extendConfig
     * @param ConfigProvider  $extendConfigProvider
     *
     * @return bool
     */
    public function isApplicableField(ConfigInterface $extendConfig, ConfigProvider $extendConfigProvider)
    {
        if ($extendConfig->has('target_entity')
            && !$this->featureChecker->isResourceEnabled($extendConfig->get('target_entity'), 'entities')
        ) {
            return false;
        }

        return
            $extendConfig->is('owner', ExtendScope::OWNER_CUSTOM)
            && ExtendHelper::isFieldAccessible($extendConfig)
            && !in_array($extendConfig->getId()->getFieldType(), RelationType::$toAnyRelations, true)
            && (
                !$extendConfig->has('target_entity')
                || ExtendHelper::isEntityAccessible(
                    $extendConfigProvider->getConfig($extendConfig->get('target_entity'))
                )
            );
    }

    /**
     * @param FormView        $view
     * @param FormInterface   $form
     * @param ConfigInterface $extendConfig
     */
    public function addInitialElements(FormView $view, FormInterface $form, ConfigInterface $extendConfig)
    {
        $data = $form->getData();
        if (!is_object($data)) {
            return;
        }
        $dataId = $this->doctrineHelper->getSingleEntityIdentifier($data);
        /**
         * 0 is default id value for oro_entity_relation
         * we need to set it if entity is new
         */
        $dataId = $dataId == null ? 0 : $dataId;

        /** @var FieldConfigId $extendConfigId */
        $extendConfigId = $extendConfig->getId();
        $className      = $extendConfigId->getClassName();
        $fieldName      = $extendConfigId->getFieldName();

        $view->children[$fieldName]->vars['grid_url'] =
            $this->router->generate(
                'oro_entity_relation',
                [
                    'id'         => $dataId,
                    'entityName' => str_replace('\\', '_', $className),
                    'fieldName'  => $fieldName
                ]
            );

        $defaultEntity = null;
        if (!$extendConfig->is('without_default')) {
            $defaultEntity = FieldAccessor::getValue(
                $data,
                ExtendConfigDumper::DEFAULT_PREFIX . $fieldName
            );
        }
        $selectedCollection = FieldAccessor::getValue($data, $fieldName);

        if ($dataId) {
            $view->children[$fieldName]->vars['initial_elements'] =
                $this->getInitialElements($selectedCollection, $defaultEntity, $extendConfig);
        }
    }

    /**
     * @param object[]        $entities
     * @param object|null     $defaultEntity
     * @param ConfigInterface $extendConfig
     *
     * @return array
     */
    public function getInitialElements($entities, $defaultEntity, ConfigInterface $extendConfig)
    {
        $result          = [];
        $className       = $extendConfig->get('target_entity');
        $identifier      = $this->getIdColumnName($className);
        $defaultEntityId = $defaultEntity !== null
            ? $this->propertyAccessor->getValue($defaultEntity, $identifier)
            : null;
        foreach ($entities as $entity) {
            $extraData = [];
            foreach ($extendConfig->get('target_grid') as $fieldName) {
                $label = $this->configManager->getProvider('entity')
                    ->getConfig($className, $fieldName)
                    ->get('label');

                $extraData[] = [
                    'label' => $this->translator->trans($label),
                    'value' => FieldAccessor::getValue($entity, $fieldName)
                ];
            }

            $title = [];
            foreach ($extendConfig->get('target_title') as $fieldName) {
                $title[] = FieldAccessor::getValue($entity, $fieldName);
            }

            /**
             * If using ExtendExtension with a form that only updates part of
             * of the entity, we need to make sure an ID is present. An ID
             * isn't present when a PHP-based Validation Constraint is fired.
             */
            $id = $this->propertyAccessor->getValue($entity, $identifier);

            if (null !== $id) {
                $result[] = [
                    'id'        => $id,
                    'label'     => implode(' ', $title),
                    'link'      => $this->router->generate(
                        'oro_entity_detailed',
                        [
                            'id'         => $id,
                            'entityName' => str_replace('\\', '_', $extendConfig->getId()->getClassName()),
                            'fieldName'  => $extendConfig->getId()->getFieldName()
                        ]
                    ),
                    'extraData' => $extraData,
                    'isDefault' => ($defaultEntity != null && $defaultEntityId === $id)
                ];
            }
        }

        return $result;
    }

    /**
     * @param string $className
     *
     * @return string
     */
    public function getIdColumnName($className)
    {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        if ($extendConfigProvider->hasConfig($className)) {
            $idColumns = $extendConfigProvider->getConfig($className)->get('pk_columns', false, ['id']);

            return reset($idColumns);
        }

        return 'id';
    }
}
