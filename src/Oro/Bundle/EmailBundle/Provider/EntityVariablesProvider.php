<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Util\Inflector;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class EntityVariablesProvider implements VariablesProviderInterface
{
    /** @var ConfigProvider */
    protected $emailConfigProvider;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param ConfigProvider $emailConfigProvider
     * @param ConfigProvider $entityConfigProvider
     * @param ConfigProvider $translator
     */
    public function __construct(
        ConfigProvider $emailConfigProvider,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $translator
    ) {
        $this->emailConfigProvider  = $emailConfigProvider;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->translator           = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateVariables(array $context = [])
    {
        if (!isset($context['entityClass'])) {
            return [];
        }

        $entityClassName = ClassUtils::getRealClass($context['entityClass']);
        if (!$this->emailConfigProvider->hasConfig($entityClassName)) {
            return [];
        }

        $variables    = [];
        $reflClass    = new \ReflectionClass($entityClassName);
        $fieldConfigs = $this->emailConfigProvider->getConfigs($entityClassName);
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

            $var = [
                'type' => $fieldId->getFieldType(),
                'name' => $this->translator->trans($this->getFieldLabel($entityClassName, $fieldId->getFieldName()))
            ];
            if ($getter) {
                $var['getter'] = $getter;
            }

            $variables[$varName] = $var;
        }

        return ['entity' => $variables];
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

        $varName = Inflector::camelize($fieldName);
        $getter  = 'get' . $varName;
        if ($reflClass->hasMethod($getter) && $reflClass->getMethod($getter)->isPublic()) {
            return [$varName, $getter];
        }

        $methodName = 'is' . $varName;
        if ($reflClass->hasMethod($methodName) && $reflClass->getMethod($methodName)->isPublic()) {
            return [$varName, $getter];
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
