<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\PropertyAccess\PropertyAccessor;
use Oro\Component\PhpUtils\ArrayUtil;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Tools\FieldAccessor;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;

class DynamicFieldsExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;
    
    /** @var ConfigManager */
    protected $configManager;

    /** @var RouterInterface */
    protected $router;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param ConfigManager       $configManager
     * @param RouterInterface     $router
     * @param TranslatorInterface $translator
     * @param DoctrineHelper      $doctrineHelper
     */
    public function __construct(
        ConfigManager $configManager,
        RouterInterface $router,
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper
    ) {
        $this->configManager    = $configManager;
        $this->router           = $router;
        $this->translator       = $translator;
        $this->doctrineHelper   = $doctrineHelper;
        $this->propertyAccessor = new PropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isApplicable($options)) {
            return;
        }

        $className = $options['data_class'];

        $extendConfigProvider = $this->configManager->getProvider('extend');
        $viewConfigProvider   = $this->configManager->getProvider('view');

        $fields      = [];
        $formConfigs = $this->configManager->getProvider('form')->getConfigs($className);
        foreach ($formConfigs as $formConfig) {
            if (!$formConfig->is('is_enabled')) {
                continue;
            }

            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $formConfig->getId();
            $fieldName     = $fieldConfigId->getFieldName();
            $extendConfig  = $extendConfigProvider->getConfig($className, $fieldName);

            if (!$this->isApplicableField($extendConfig, $extendConfigProvider)) {
                continue;
            }

            $fields[$fieldName] = [
                'priority' => $viewConfigProvider->getConfig($className, $fieldName)->get('priority', false, 0)
            ];
        }

        ArrayUtil::sortBy($fields, true);

        foreach ($fields as $fieldName => $priority) {
            $builder->add($fieldName);
        }
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!$this->isApplicable($options)) {
            return;
        }

        $className = $options['data_class'];

        $extendConfigProvider = $this->configManager->getProvider('extend');

        $formConfigs = $this->configManager->getProvider('form')->getConfigs($className);
        foreach ($formConfigs as $formConfig) {
            if (!$formConfig->is('is_enabled')) {
                continue;
            }

            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $formConfig->getId();
            $fieldName     = $fieldConfigId->getFieldName();

            $extendConfig = $extendConfigProvider->getConfig($className, $fieldName);
            if (!$this->isApplicableField($extendConfig, $extendConfigProvider)) {
                continue;
            }

            // check if a field exists, because it is possible that it can be removed by some event listener
            if (!isset($view->children[$fieldName])) {
                continue;
            }

            $view->children[$fieldName]->vars['extra_field'] = true;

            if (!in_array($fieldConfigId->getFieldType(), RelationType::$toManyRelations, true)) {
                continue;
            }

            $this->addInitialElements($view, $form, $extendConfig);
        }
    }

    /**
     * @param FormView        $view
     * @param FormInterface   $form
     * @param ConfigInterface $extendConfig
     */
    protected function addInitialElements(FormView $view, FormInterface $form, ConfigInterface $extendConfig)
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
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'dynamic_fields_disabled' => false,
            ]
        );
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    protected function isApplicable(array $options)
    {
        if ($options['dynamic_fields_disabled'] || empty($options['data_class'])) {
            return false;
        }

        $className = $options['data_class'];
        if (!$this->doctrineHelper->isManageableEntity($className)) {
            return false;
        }
        if (!$this->configManager->getProvider('extend')->hasConfig($className)) {
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
    protected function isApplicableField(ConfigInterface $extendConfig, ConfigProvider $extendConfigProvider)
    {
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
     * @param object[]        $entities
     * @param object|null     $defaultEntity
     * @param ConfigInterface $extendConfig
     *
     * @return array
     */
    protected function getInitialElements($entities, $defaultEntity, ConfigInterface $extendConfig)
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
    protected function getIdColumnName($className)
    {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        if ($extendConfigProvider->hasConfig($className)) {
            $idColumns = $extendConfigProvider->getConfig($className)->get('pk_columns', false, ['id']);

            return reset($idColumns);
        }

        return 'id';
    }
}
