<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;

/**
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class ConfigScopeType extends AbstractType
{
    /**
     * @var array
     */
    protected $items;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var ConfigModel
     */
    protected $configModel;

    /**
     * @var array
     */
    protected $jsRequireOptions;

    /**
     * @param $items
     * @param $config
     * @param $configModel
     * @param $configManager
     */
    public function __construct(
        $items,
        ConfigInterface $config,
        ConfigManager $configManager,
        ConfigModel $configModel
    ) {
        $this->items         = $items;
        $this->config        = $config;
        $this->configModel   = $configModel;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->items as $code => $config) {
            if (isset($config['form']['type'])) {
                $options = isset($config['form']['options']) ? $config['form']['options'] : array();

                $options['config_id']     = $this->config->getId();
                $options['config_is_new'] = $this->configModel->getId() == false;

                /**
                 * Disable field on editAction
                 */
                if (isset($config['options']['create_only']) && $this->configModel->getId()) {
                    $options['disabled'] = true;
                    $this->appendClassAttr($options, 'disabled-' . $config['form']['type']);
                }
                $propertyOnForm = false;
                $properties = [];
                if (isset($config['options']['required_property'])) {
                    $properties[] = $config['options']['required_property'];
                }
                if (isset($config['options']['required_properties'])) {
                    $properties = array_merge($properties, $config['options']['required_properties']);
                }

                if (!empty($properties)) {
                    foreach ($properties as $property) {
                        if (isset($property['config_id'])) {
                            $configId = $property['config_id'];

                            $fieldName = array_key_exists('field_name', $configId) ? $configId['field_name'] : false;
                            if ($fieldName === false && $this->config->getId() instanceof FieldConfigId) {
                                $fieldName = $this->config->getId()->getFieldName();
                            }

                            $className = isset($configId['class_name'])
                                ? $configId['class_name']
                                : $this->config->getId()->getClassName();

                            $scope = isset($configId['scope'])
                                ? $configId['scope']
                                : $this->config->getId()->getScope();

                            if ($fieldName) {
                                $configId = new FieldConfigId(
                                    $scope,
                                    $className,
                                    $fieldName,
                                    $this->config->getId()->getFieldType()
                                );
                            } else {
                                $configId = new EntityConfigId($scope, $className);
                            }

                            //check if requirement property is set in this form
                            if ($className == $this->config->getId()->getClassName()) {
                                if ($fieldName) {
                                    if ($this->config->getId() instanceof FieldConfigId
                                        && $this->config->getId()->getFieldName() == $fieldName
                                    ) {
                                        $propertyOnForm = true;
                                    }
                                } else {
                                    $propertyOnForm = true;
                                }
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
                    $options['constraints'] = $this->parseValidator($config['constraints']);
                }

                $this->setAttr($options, 'data-property_id', $this->config->getId()->toString() . $code);

                $builder->add($code, $config['form']['type'], $options);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_config_scope_type';
    }

    /**
     * @param $name
     * @param $options
     * @return mixed
     *
     * TODO: use ConstraintFactory here, https://magecore.atlassian.net/browse/BAP-2270
     */
    protected function newConstraint($name, $options)
    {
        if (strpos($name, '\\') !== false && class_exists($name)) {
            $className = (string) $name;
        } else {
            $className = 'Symfony\\Component\\Validator\\Constraints\\' . $name;
        }

        return new $className($options);
    }

    /**
     * @param array $nodes
     * @return array
     */
    protected function parseValidator(array $nodes)
    {
        $values = array();

        foreach ($nodes as $name => $childNodes) {
            if (is_numeric($name) && is_array($childNodes) && count($childNodes) == 1) {
                $options = current($childNodes);

                if (is_array($options)) {
                    $options = $this->parseValidator($options);
                }

                $values[] = $this->newConstraint(key($childNodes), $options);
            } else {
                if (is_array($childNodes)) {
                    $childNodes = $this->parseValidator($childNodes);
                }

                $values[$name] = $childNodes;
            }
        }

        return $values;
    }

    protected function appendClassAttr(array &$options, $cssClass)
    {
        if (isset($options['attr']['class'])) {
            $options['attr']['class'] .= ' ' . $cssClass;
        } else {
            $this->setAttr($options, 'class', $cssClass);
        }
    }

    protected function setAttr(array &$options, $name, $value)
    {
        if (!isset($options['attr'])) {
            $options['attr'] = [];
        }
        $options['attr'][$name] = $value;
    }
}
