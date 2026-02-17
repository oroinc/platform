<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamilyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Config\AttributeConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\EntityExtendBundle\Form\Util\DynamicFieldsHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Oro\Component\PhpUtils\ArrayUtil;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Adds attributes to a form based on the configuration for an entity.
 */
class DynamicAttributesExtension extends AbstractTypeExtension implements ServiceSubscriberInterface
{
    use FormExtendedTypeTrait;

    private array $fields = [];

    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ConfigManager::class,
            DoctrineHelper::class,
            AttributeManager::class,
            AttributeConfigHelper::class,
            DynamicFieldsHelper::class
        ];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$this->isApplicable($options)) {
            return;
        }

        $dataClass = $options['data_class'];
        $viewConfigProvider = $this->getConfigManager()->getProvider('view');

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

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$this->isApplicable($options)) {
            return;
        }

        $className = $options['data_class'];

        $configManager = $this->getConfigManager();
        $extendConfigProvider = $configManager->getProvider('extend');
        $attributeConfigProvider = $configManager->getProvider('attribute');

        $formConfigs = $configManager->getProvider('form')->getConfigs($className);
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

            if (!$this->getDynamicFieldsHelper()->shouldBeInitialized($className, $formConfig, $view)) {
                continue;
            }

            $extendConfig = $extendConfigProvider->getConfig($className, $fieldName);

            $this->getDynamicFieldsHelper()->addInitialElements($view, $form, $extendConfig);
        }
    }

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

    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $dataClass = $form->getConfig()->getOption('data_class');

        if (empty($data['attributeFamily'])) {
            return;
        }

        $attributeFamily = $this->getDoctrineHelper()
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
        $formConfigs = $this->getConfigManager()->getProvider('form')->getConfigs($class);
        $fieldNames = [];
        foreach ($formConfigs as $formConfig) {
            if (!$formConfig->is('is_enabled')) {
                continue;
            }

            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $formConfig->getId();
            $fieldName = $fieldConfigId->getFieldName();

            if ($this->getAttributeConfigHelper()->isFieldAttribute($class, $fieldName)) {
                $fieldNames[] = $fieldName;
            }
        }

        return $fieldNames;
    }

    private function isApplicable(array $options): bool
    {
        if (empty($options['data_class'])) {
            return false;
        }

        if (empty($options['enable_attributes'])) {
            return false;
        }

        if (!$this->getAttributeConfigHelper()->isEntityWithAttributes($options['data_class'])) {
            return false;
        }

        return true;
    }

    /**
     * @param FormInterface $form
     * @param string $dataClass
     * @param AttributeFamily|null $attributeFamily
     */
    private function addAttributes(FormInterface $form, $dataClass, ?AttributeFamily $attributeFamily = null)
    {
        if (!$attributeFamily || empty($this->fields[$dataClass])) {
            return;
        }

        $attributes = $this->getAttributeManager()->getAttributesByFamily($attributeFamily);
        foreach ($this->fields[$dataClass] as $fieldName => $priority) {
            foreach ($attributes as $attribute) {
                if ($fieldName === $attribute->getFieldName() && !$form->has($fieldName)) {
                    ExtendHelper::isEnumerableType($attribute->getType())
                        ? $form->add($fieldName, options: [
                        'query_builder' => function (EnumOptionRepository $repo) use ($attribute) {
                            return $repo->getValuesQueryBuilder($attribute->get());
                        },
                        'multiple' => ExtendHelper::isMultiEnumType($attribute->getType()),
                        'empty_data' => ExtendHelper::isMultiEnumType($attribute->getType()) ? [] : null
                    ]) : $form->add($fieldName);
                    break;
                }
            }
        }
    }

    private function getConfigManager(): ConfigManager
    {
        return $this->container->get(ConfigManager::class);
    }

    private function getDoctrineHelper(): DoctrineHelper
    {
        return $this->container->get(DoctrineHelper::class);
    }

    private function getAttributeManager(): AttributeManager
    {
        return $this->container->get(AttributeManager::class);
    }

    private function getAttributeConfigHelper(): AttributeConfigHelper
    {
        return $this->container->get(AttributeConfigHelper::class);
    }

    private function getDynamicFieldsHelper(): DynamicFieldsHelper
    {
        return $this->container->get(DynamicFieldsHelper::class);
    }
}
