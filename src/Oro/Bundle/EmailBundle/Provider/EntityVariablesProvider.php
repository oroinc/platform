<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Util\Inflector;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class EntityVariablesProvider implements VariablesProviderInterface
{
    /** @var ConfigProvider */
    protected $emailConfigProvider;

    /**
     * @param ConfigProvider $emailConfigProvider
     */
    public function __construct(ConfigProvider $emailConfigProvider)
    {
        $this->emailConfigProvider = $emailConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateVariables(array $context = [])
    {
        if (!isset($context['entityName'])) {
            return [];
        }

        $entityClassName = $context['entityName'];
        if (!$this->emailConfigProvider->hasConfig($entityClassName)) {
            return [];
        }

        $fields = [];

        $reflClass = new \ReflectionClass($entityClassName);
        $fieldConfigs = $this->emailConfigProvider->getConfigs($entityClassName);
        foreach ($fieldConfigs as $fieldConfig) {
            if (!$fieldConfig->is('available_in_template')) {
                continue;
            }

            $fieldName = $fieldConfig->getId()->getFieldName();
            $getter = null;
            if ($reflClass->hasProperty($fieldName) && $reflClass->getProperty($fieldName)->isPublic()) {
                $varName = $fieldName;
            } else {
                $varName = Inflector::camelize($fieldName);
                $methodName = 'get' . $varName;
                if ($reflClass->hasMethod($methodName) && $reflClass->getMethod($methodName)->isPublic()) {
                    $getter = $methodName;
                } else {
                    $methodName = 'is' . $varName;
                    if ($reflClass->hasMethod($methodName) && $reflClass->getMethod($methodName)->isPublic()) {
                        $getter = $methodName;
                    }
                }
            }

            method_exists()

            $varName = Inflector::camelize($fieldConfig->getId()->getFieldName());
        }
        $fields = array_values(
            array_map(
                function (ConfigInterface $field) {
                    return Inflector::camelize($field->getId()->getFieldName());
                },
                $fields
            )
        );

        return ['entity' => $fields];
    }

    /**
     * @param \ReflectionClass $reflClass
     * @param string           $fieldName
     * @return array [variable name, getter method name]
     */
    protected function getFieldAccessInfo(\ReflectionClass $reflClass, $fieldName)
    {
        $getter = null;
        if ($reflClass->hasProperty($fieldName) && $reflClass->getProperty($fieldName)->isPublic()) {
            return [$fieldName, null];
        }

        $varName = Inflector::camelize($fieldName);
        $getter = 'get' . $varName;
        if ($reflClass->hasMethod($getter) && $reflClass->getMethod($getter)->isPublic()) {
            return [$varName, $getter];
        }

        $methodName = 'is' . $varName;
        if ($reflClass->hasMethod($methodName) && $reflClass->getMethod($methodName)->isPublic()) {
            return [$varName, $getter];
        }

        return [null, null];
    }
}
