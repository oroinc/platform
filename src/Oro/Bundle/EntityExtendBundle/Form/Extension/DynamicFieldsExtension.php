<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Form\Util\DynamicFieldsHelper;
use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds extended fields to the form based on the configuration for the entity/field.
 */
class DynamicFieldsExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;
    
    /** @var ConfigManager */
    protected $configManager;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var DynamicFieldsHelper */
    private $dynamicFieldsHelper;

    /**
     * @param ConfigManager                        $configManager
     * @param DoctrineHelper                       $doctrineHelper
     * @param DynamicFieldsHelper                  $dynamicFieldsHelper
     */
    public function __construct(
        ConfigManager $configManager,
        DoctrineHelper $doctrineHelper,
        DynamicFieldsHelper $dynamicFieldsHelper
    ) {
        $this->configManager    = $configManager;
        $this->doctrineHelper   = $doctrineHelper;
        $this->dynamicFieldsHelper = $dynamicFieldsHelper;
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
        $attributeConfigProvider  = $this->configManager->getProvider('attribute');

        $fields      = [];
        $formConfigs = $this->configManager->getProvider('form')->getConfigs($className);
        foreach ($formConfigs as $formConfig) {
            if (!$formConfig->is('is_enabled')) {
                continue;
            }

            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $formConfig->getId();
            $fieldName     = $fieldConfigId->getFieldName();

            $attributeConfig = $attributeConfigProvider->getConfig($className, $fieldName);
            if ($attributeConfig->is('is_attribute')) {
                continue;
            }

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
        $attributeConfigProvider  = $this->configManager->getProvider('attribute');

        $formConfigs = $this->configManager->getProvider('form')->getConfigs($className);
        foreach ($formConfigs as $formConfig) {
            if (!$formConfig->is('is_enabled')) {
                continue;
            }

            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $formConfig->getId();
            $fieldName     = $fieldConfigId->getFieldName();

            $attributeConfig = $attributeConfigProvider->getConfig($className, $fieldName);
            if ($attributeConfig->is('is_attribute')) {
                continue;
            }

            if (!$this->dynamicFieldsHelper->shouldBeInitialized($className, $formConfig, $view, true)) {
                continue;
            }

            $extendConfig = $extendConfigProvider->getConfig($className, $fieldName);

            $this->dynamicFieldsHelper->addInitialElements($view, $form, $extendConfig);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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
     * @deprecated please use \Oro\Bundle\EntityExtendBundle\Form\Util\DynamicFieldsHelper::isApplicableField()
     */
    protected function isApplicableField(ConfigInterface $extendConfig, ConfigProvider $extendConfigProvider)
    {
        return $this->dynamicFieldsHelper->isApplicableField($extendConfig, $extendConfigProvider);
    }
}
