<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form for entity and entity field configuration options grouped by a scope.
 */
class ConfigScopeType extends AbstractType
{
    /** @var ConfigInterface */
    private $config;

    /** @var ConfigManager */
    private $configManager;

    /** @var ConfigModel */
    private $configModel;

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['items', 'config', 'config_model', 'config_manager']);
        $resolver->setAllowedTypes('items', 'array');
        $resolver->setAllowedTypes('config', ConfigInterface::class);
        $resolver->setAllowedTypes('config_model', ConfigModel::class);
        $resolver->setAllowedTypes('config_manager', ConfigManager::class);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->config = $options['config'];
        $this->configModel = $options['config_model'];
        $this->configManager = $options['config_manager'];

        foreach ($options['items'] as $code => $config) {
            if (!isset($config['form']['type'])) {
                continue;
            }

            $options = $config['form']['options'] ?? [];
            $options['config_id'] = $this->config->getId();
            $options['config_is_new'] = !$this->configModel->getId();

            if ($this->isDisabledItem($config)) {
                $options['disabled'] = true;
                $this->appendClassAttr($options, 'disabled-' . $config['form']['type']);
            }

            $propertyOnForm = false;
            $properties = $this->getRequiredProperties($config);
            if (!empty($properties)) {
                foreach ($properties as $property) {
                    if (isset($property['config_id'])) {
                        $configId = $this->createConfigId($property['config_id']);
                        // check if requirement property is set in this form
                        if ($this->isPropertyOnForm($configId)) {
                            $propertyOnForm = true;
                        }
                    } else {
                        $propertyOnForm = true;
                        $configId = $this->config->getId();
                    }

                    $requireConfig = $this->configManager->getConfig($configId);
                    if ($requireConfig->get($property['code']) != $property['value']) {
                        if ($propertyOnForm) {
                            $this->appendClassAttr($options, 'hide');
                        } else {
                            continue;
                        }
                    }
                }

                if ($propertyOnForm) {
                    $this->setAttr($options, 'data-requireProperty', $configId->toString() . $property['code']);
                    $this->setAttr($options, 'data-requireValue', $property['value']);
                }
            }

            if (isset($config['constraints'])) {
                $options['constraints'] = $config['constraints'];
            }

            $this->setAttr($options, 'data-property_id', $this->config->getId()->toString() . $code);

            $builder->add($code, $config['form']['type'], $options);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_entity_config_scope_type';
    }

    /**
     * @param array $config
     *
     * @return bool
     */
    private function isDisabledItem(array $config)
    {
        $createOnly = isset($config['options']['create_only']) && $config['options']['create_only'];

        // disable config attribute if its value cannot be changed
        if ($createOnly && $this->configModel->getId()) {
            return true;
        }

        $configId = $this->config->getId();

        // disable field config attribute if its value cannot be changed for some field types
        // an attribute marked as create only should not be disabled on create field page
        return
            $configId instanceof FieldConfigId
            && !empty($config['options']['immutable_type'])
            && in_array($configId->getFieldType(), $config['options']['immutable_type'], true)
            && (!$createOnly || $this->configModel->getId());
    }

    /**
     * @param array  $options
     * @param string $cssClass
     */
    private function appendClassAttr(array &$options, $cssClass)
    {
        if (isset($options['attr']['class'])) {
            $options['attr']['class'] .= ' ' . $cssClass;
        } else {
            $this->setAttr($options, 'class', $cssClass);
        }
    }

    /**
     * @param array  $options
     * @param string $name
     * @param mixed  $value
     */
    private function setAttr(array &$options, $name, $value)
    {
        if (!isset($options['attr'])) {
            $options['attr'] = [];
        }
        $options['attr'][$name] = $value;
    }

    /**
     * @param array $config
     *
     * @return array
     */
    private function getRequiredProperties(array $config)
    {
        $properties = [];
        if (isset($config['options']['required_property'])) {
            $properties[] = $config['options']['required_property'];
        }
        if (isset($config['options']['required_properties'])) {
            $properties = array_merge($properties, $config['options']['required_properties']);
        }

        return $properties;
    }

    /**
     * @param array $config
     *
     * @return string
     */
    private function getScope(array $config)
    {
        return $config['scope'] ?? $this->config->getId()->getScope();
    }

    /**
     * @param array $config
     *
     * @return string
     */
    private function getClassName(array $config)
    {
        return $config['class_name'] ?? $this->config->getId()->getClassName();
    }

    /**
     * @param array $config
     *
     * @return string|null
     */
    private function getFieldName(array $config)
    {
        $fieldName = array_key_exists('field_name', $config) ? $config['field_name'] : false;
        if (false === $fieldName) {
            $configId = $this->config->getId();
            if ($configId instanceof FieldConfigId) {
                $fieldName = $configId->getFieldName();
            } else {
                $fieldName = null;
            }
        }

        return $fieldName;
    }

    /**
     * @param array $config
     *
     * @return ConfigIdInterface
     */
    private function createConfigId(array $config)
    {
        $scope = $this->getScope($config);
        $className = $this->getClassName($config);
        $fieldName = $this->getFieldName($config);

        if ($fieldName) {
            return new FieldConfigId(
                $scope,
                $className,
                $fieldName,
                $this->config->getId()->getFieldType()
            );
        }

        return new EntityConfigId($scope, $className);
    }

    /**
     * @param ConfigIdInterface $configId
     *
     * @return bool
     */
    private function isPropertyOnForm(ConfigIdInterface $configId)
    {
        $propertyOnForm = false;
        $configuredConfigId = $this->config->getId();
        if ($configId->getClassName() === $configuredConfigId->getClassName()) {
            if ($configId instanceof FieldConfigId) {
                if ($configuredConfigId instanceof FieldConfigId
                    && $configuredConfigId->getFieldName() === $configId->getFieldName()
                ) {
                    $propertyOnForm = true;
                }
            } else {
                $propertyOnForm = true;
            }
        }

        return $propertyOnForm;
    }
}
