<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Helper\ConfigModelConstraintsHelper;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form for entity and entity field configuration options grouped by a scope.
 */
class ConfigScopeType extends AbstractType
{
    private ConfigManager $entityConfigManager;

    public function __construct(ConfigManager $entityConfigManager)
    {
        $this->entityConfigManager = $entityConfigManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['items', 'config', 'config_model']);
        $resolver->setAllowedTypes('items', 'array');
        $resolver->setAllowedTypes('config', ConfigInterface::class);
        $resolver->setAllowedTypes('config_model', ConfigModel::class);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ConfigIdInterface $scopeConfigId */
        $scopeConfigId = $options['config']->getId();
        /** @var ConfigModel $scopeConfigModel */
        $scopeConfigModel = $options['config_model'];

        foreach ($options['items'] as $itemCode => $itemConfig) {
            if (!isset($itemConfig['form']['type'])) {
                continue;
            }

            // Skips applying required properties restrictions because the config is new.
            if ($scopeConfigModel->getId()) {
                $requiredProperties = $this->getRequiredProperties($itemConfig);
                foreach ($requiredProperties as $property) {
                    $propertyConfigId = $this->createConfigId($property, $scopeConfigId);
                    $propertyCurrentValue = $this->entityConfigManager
                        ->getConfig($propertyConfigId)
                        ->get($property['code']);

                    // Non-identical comparison is used on purpose.
                    if ($propertyCurrentValue != $property['value']) {
                        // Skips adding $itemCode to form as the required property value condition is not satisfied.
                        continue 2;
                    }
                }
            }

            $builder->add($itemCode, $itemConfig['form']['type'], $this->getItemOptions($itemCode, $options));
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    private function getItemOptions(string $itemCode, array $options): array
    {
        /** @var ConfigIdInterface $scopeConfigId */
        $scopeConfigId = $options['config']->getId();
        /** @var ConfigModel $scopeConfigModel */
        $scopeConfigModel = $options['config_model'];

        $itemConfig = $options['items'][$itemCode];
        $itemOptions = array_merge_recursive($itemConfig['form']['options'] ?? [], [
            'config_id' => $scopeConfigId,
            'config_is_new' => !$scopeConfigModel->getId(),
        ]);

        $itemOptions['disabled'] = $this->isDisabledItem($itemConfig, $itemOptions);

        if (isset($itemConfig['constraints'])) {
            $itemOptions['constraints'] = ConfigModelConstraintsHelper::configureConstraintsWithConfigModel(
                $itemConfig['constraints'],
                $scopeConfigModel
            );
        }

        return $itemOptions;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var ConfigIdInterface $scopeConfigId */
        $scopeConfigId = $options['config']->getId();

        foreach ($options['items'] as $itemCode => $itemConfig) {
            if ($form->has($itemCode)) {
                $child = $form->get($itemCode);
                if ($child->isDisabled()) {
                    FormUtils::appendClass($view[$itemCode], 'disabled');
                }

                $itemConfigId = $child->getConfig()->getOption('config_id');
                $view[$itemCode]->vars['attr']['data-property_id'] = $itemConfigId->toString() . $itemCode;
            } else {
                $itemConfigId = $scopeConfigId;
            }

            $requiredProperties = $this->getRequiredProperties($options['items'][$itemCode]);
            foreach ($requiredProperties as $property) {
                $propertyConfigId = $this->createConfigId($property, $itemConfigId);

                // Adds required-property-view component if required property exists in the same scope.
                if ($propertyConfigId === $itemConfigId && isset($view[$property['code']])) {
                    $view[$property['code']]->vars['attr'] = array_merge(
                        $view[$property['code']]->vars['attr'] ?? [],
                        [
                            'data-page-component-module' => 'oroui/js/app/components/view-component',
                            'data-page-component-options' => json_encode([
                                'view' => 'oroentityconfig/js/views/required-property-view',
                            ]),
                        ]
                    );
                }

                if (isset($view[$itemCode]) && $this->isPropertyExists($propertyConfigId, $itemConfigId)) {
                    $view[$itemCode]->vars['attr'] = array_merge($view[$itemCode]->vars['attr'], [
                        'data-requireProperty' => $propertyConfigId->toString() . $property['code'],
                        'data-requireValue' => $property['value'],
                    ]);
                }
            }
        }
    }

    public function onPreSubmit(PreSubmitEvent $event): void
    {
        $submittedData = $event->getData();
        $form = $event->getForm();
        $options = $form->getConfig()->getOptions();
        /** @var ConfigIdInterface $scopeConfigId */
        $scopeConfigId = $options['config']->getId();

        foreach ($options['items'] as $itemCode => $itemConfig) {
            if ($form->has($itemCode)) {
                $itemConfigId = $form->get($itemCode)->getConfig()->getOption('config_id');
                $itemPresent = true;
            } else {
                $itemConfigId = $scopeConfigId;
                $itemPresent = false;
            }

            $requiredProperties = $this->getRequiredProperties($itemConfig);
            $isSatisfied = false;
            foreach ($requiredProperties as $property) {
                $propertyConfigId = $this->createConfigId($property, $itemConfigId);

                // If the required property exists in the same scope and the required property value condition
                // is not satisfied.
                if ($propertyConfigId === $itemConfigId) {
                    $requiredPropertyValue = $this->getRequiredPropertyValue($property['code'], $submittedData, $form);
                    // Non-identical comparison is used on purpose.
                    if ($requiredPropertyValue != $property['value']) {
                        // Removes $itemCode as the required property value condition is not satisfied.
                        $form->remove($itemCode);
                        unset($submittedData[$itemCode]);
                        $isSatisfied = false;
                        break;
                    }
                    $isSatisfied = !$itemPresent;
                }
            }

            if ($isSatisfied) {
                $form->add($itemCode, $itemConfig['form']['type'], $this->getItemOptions($itemCode, $options));
            }
        }

        $event->setData($submittedData);
    }

    private function getRequiredPropertyValue(string $name, ?array $submittedData, FormInterface $form): mixed
    {
        if ($form->has($name) && !$form->get($name)->isDisabled()) {
            return $submittedData[$name] ?? null;
        }

        return $form->getData()[$name] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_entity_config_scope_type';
    }

    private function isDisabledItem(array $itemConfig, array $itemOptions): bool
    {
        $createOnly = !empty($itemConfig['options']['create_only']);

        // disable config attribute if its value cannot be changed
        if ($createOnly && !$itemOptions['config_is_new']) {
            return true;
        }

        // disable field config attribute if its value cannot be changed for some field types
        // an attribute marked as create only should not be disabled on create field page
        return
            $itemOptions['config_id'] instanceof FieldConfigId
            && !empty($itemConfig['immutable_type'])
            && in_array($itemOptions['config_id']->getFieldType(), $itemConfig['immutable_type'], true)
            && (!$createOnly || !$itemOptions['config_is_new']);
    }

    private function getRequiredProperties(array $itemConfig): array
    {
        $properties = [];
        if (isset($itemConfig['options']['required_property'])) {
            $properties[] = $itemConfig['options']['required_property'];
        }
        if (isset($itemConfig['options']['required_properties'])) {
            $properties = array_merge($properties, $itemConfig['options']['required_properties']);
        }

        return $properties;
    }

    private function createConfigId(array $requiredPropertyConfig, ConfigIdInterface $configId): ConfigIdInterface
    {
        if (isset($requiredPropertyConfig['config_id'])) {
            $requiredPropertyConfig['config_id'] += [
                'scope' => $configId->getScope(),
                'class_name' => $configId->getClassName(),
                'field_name' => $configId instanceof FieldConfigId ? $configId->getFieldName() : null,
            ];

            if ($requiredPropertyConfig['config_id']['field_name']) {
                return new FieldConfigId(
                    $requiredPropertyConfig['config_id']['scope'],
                    $requiredPropertyConfig['config_id']['class_name'],
                    $requiredPropertyConfig['config_id']['field_name'],
                    $configId->getFieldType()
                );
            }

            return new EntityConfigId(
                $requiredPropertyConfig['config_id']['scope'],
                $requiredPropertyConfig['config_id']['class_name']
            );
        }

        return $configId;
    }

    private function isPropertyExists(
        ConfigIdInterface $requiredPropertyConfigId,
        ConfigIdInterface $itemConfigId
    ): bool {
        $exists = false;
        if ($requiredPropertyConfigId->getClassName() === $itemConfigId->getClassName()) {
            if ($requiredPropertyConfigId instanceof FieldConfigId) {
                if ($itemConfigId instanceof FieldConfigId
                    && $itemConfigId->getFieldName() === $requiredPropertyConfigId->getFieldName()
                ) {
                    $exists = true;
                }
            } else {
                $exists = true;
            }
        }

        return $exists;
    }
}
