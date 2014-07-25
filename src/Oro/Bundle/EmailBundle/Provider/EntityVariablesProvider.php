<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Util\Inflector;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class EntityVariablesProvider implements EntityVariablesProviderInterface
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var ConfigProvider */
    protected $emailConfigProvider;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /**
     * @param TranslatorInterface $translator
     * @param ConfigProvider      $emailConfigProvider
     * @param ConfigProvider      $entityConfigProvider
     */
    public function __construct(
        TranslatorInterface $translator,
        ConfigProvider $emailConfigProvider,
        ConfigProvider $entityConfigProvider
    ) {
        $this->translator           = $translator;
        $this->emailConfigProvider  = $emailConfigProvider;
        $this->entityConfigProvider = $entityConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableDefinitions($entityClass)
    {
        $entityClass = ClassUtils::getRealClass($entityClass);
        if (!$this->emailConfigProvider->hasConfig($entityClass)) {
            return [];
        }

        $result       = [];
        $reflClass    = new \ReflectionClass($entityClass);
        $fieldConfigs = $this->emailConfigProvider->getConfigs($entityClass);
        foreach ($fieldConfigs as $fieldConfig) {
            if (!$fieldConfig->is('available_in_template')) {
                continue;
            }

            /** @var FieldConfigId $fieldId */
            $fieldId = $fieldConfig->getId();

            list($varName) = $this->getFieldAccessInfo($reflClass, $fieldId->getFieldName());
            if (!$varName) {
                continue;
            }

            $var = [
                'type' => $fieldId->getFieldType(),
                'name' => $this->translator->trans($this->getFieldLabel($entityClass, $fieldId->getFieldName()))
            ];

            $result[$varName] = $var;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableGetters($entityClass)
    {
        $entityClass = ClassUtils::getRealClass($entityClass);
        if (!$this->emailConfigProvider->hasConfig($entityClass)) {
            return [];
        }

        $result       = [];
        $reflClass    = new \ReflectionClass($entityClass);
        $fieldConfigs = $this->emailConfigProvider->getConfigs($entityClass);
        foreach ($fieldConfigs as $fieldConfig) {
            if (!$fieldConfig->is('available_in_template')) {
                continue;
            }

            /** @var FieldConfigId $fieldId */
            $fieldId = $fieldConfig->getId();

            list($varName, $getter) = $this->getFieldAccessInfo($reflClass, $fieldId->getFieldName());
            if (!$varName) {
                continue;
            }

            $result[$varName] = $getter;
        }

        return $result;
    }

    /**
     * @param \ReflectionClass $reflClass
     * @param string           $fieldName
     *
     * @return array [variable name, getter method name]
     */
    protected function getFieldAccessInfo(\ReflectionClass $reflClass, $fieldName)
    {
        $getter = null;
        if ($reflClass->hasProperty($fieldName) && $reflClass->getProperty($fieldName)->isPublic()) {
            return [$fieldName, null];
        }

        $name   = Inflector::classify($fieldName);
        $getter = 'get' . $name;
        if ($reflClass->hasMethod($getter) && $reflClass->getMethod($getter)->isPublic()) {
            return [lcfirst($name), $getter];
        }

        $methodName = 'is' . $name;
        if ($reflClass->hasMethod($methodName) && $reflClass->getMethod($methodName)->isPublic()) {
            return [lcfirst($name), $getter];
        }

        return [null, null];
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return string
     */
    protected function getFieldLabel($className, $fieldName)
    {
        return $this->entityConfigProvider->getConfig($className, $fieldName)->get('label');
    }
}
