<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamilyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Config\AttributeConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityExtendBundle\Form\Util\DynamicFieldsHelper;
use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class DynamicAttributesExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;
    
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var AttributeManager
     */
    private $attributeManager;

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var AttributeConfigHelper
     */
    private $attributeConfigHelper;

    /**
     * @var DynamicFieldsHelper
     */
    private $dynamicFieldsHelper;

    /**
     * @param ConfigManager $configManager
     * @param DoctrineHelper $doctrineHelper
     * @param AttributeManager $attributeManager
     * @param AttributeConfigHelper $attributeConfigHelper
     */
    public function __construct(
        ConfigManager $configManager,
        DoctrineHelper $doctrineHelper,
        AttributeManager $attributeManager,
        AttributeConfigHelper $attributeConfigHelper,
        DynamicFieldsHelper $dynamicFieldsHelper
    ) {
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->attributeManager = $attributeManager;
        $this->attributeConfigHelper = $attributeConfigHelper;
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

        $dataClass = $options['data_class'];
        $viewConfigProvider = $this->configManager->getProvider('view');

        foreach ($this->getFieldsByClass($dataClass) as $fieldName) {
            $this->fields[$dataClass][$fieldName] = [
                'priority' => $viewConfigProvider->getConfig($dataClass, $fieldName)->get('priority', false, 0)
            ];
        }

        if (!empty($this->fields[$dataClass])) {
            ArrayUtil::sortBy($this->fields[$dataClass], true);
            $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
            $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
        }
    }

    /**
     * {@inheritdoc}
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
            if (!$attributeConfig->is('is_attribute')) {
                continue;
            }

            if (!$this->dynamicFieldsHelper->shouldBeInitialized($className, $formConfig, $view)) {
                continue;
            }

            $extendConfig = $extendConfigProvider->getConfig($className, $fieldName);

            $this->dynamicFieldsHelper->addInitialElements($view, $form, $extendConfig);
        }
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $dataClass = $form->getConfig()->getOption('data_class');

        if (!$data instanceof AttributeFamilyAwareInterface || !$data->getAttributeFamily()) {
            return;
        }

        $this->addAttributes($form, $dataClass, $data->getAttributeFamily());
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $dataClass = $form->getConfig()->getOption('data_class');

        if (empty($data['attributeFamily'])) {
            return;
        }

        $attributeFamily = $this->doctrineHelper
            ->getEntityRepositoryForClass(AttributeFamily::class)
            ->find((int)$data['attributeFamily']);

        $this->addAttributes($event->getForm(), $dataClass, $attributeFamily);
    }

    /**
     * @param string $class
     * @return array
     */
    private function getFieldsByClass($class)
    {
        $formConfigs = $this->configManager->getProvider('form')->getConfigs($class);
        $fieldNames = [];
        foreach ($formConfigs as $formConfig) {
            if (!$formConfig->is('is_enabled')) {
                continue;
            }

            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $formConfig->getId();
            $fieldName = $fieldConfigId->getFieldName();

            if ($this->attributeConfigHelper->isFieldAttribute($class, $fieldName)) {
                $fieldNames[] = $fieldName;
            }
        }

        return $fieldNames;
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    private function isApplicable(array $options)
    {
        if (empty($options['data_class'])) {
            return false;
        }
        
        if (empty($options['enable_attributes'])) {
            return false;
        }

        if (!$this->attributeConfigHelper->isEntityWithAttributes($options['data_class'])) {
            return false;
        }

        return true;
    }

    /**
     * @param FormInterface $form
     * @param string $dataClass
     * @param AttributeFamily $attributeFamily
     */
    private function addAttributes(FormInterface $form, $dataClass, AttributeFamily $attributeFamily = null)
    {
        if (!$attributeFamily || empty($this->fields[$dataClass])) {
            return;
        }

        $attributes = $this->attributeManager->getAttributesByFamily($attributeFamily);
        foreach ($this->fields[$dataClass] as $fieldName => $priority) {
            foreach ($attributes as $attribute) {
                if ($fieldName === $attribute->getFieldName() && !$form->has($fieldName)) {
                    $form->add($fieldName);
                    break;
                }
            }
        }
    }
}
