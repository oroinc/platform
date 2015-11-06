<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Util\Inflector;
use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\UIBundle\Formatter\FormatterManager;

class EntityVariablesProvider implements EntityVariablesProviderInterface
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var ConfigProvider */
    protected $emailConfigProvider;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var FormatterManager */
    protected $formatterManager;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * EntityVariablesProvider constructor.
     *
     * @param TranslatorInterface $translator
     * @param ConfigManager       $configManager
     * @param ManagerRegistry     $doctrine
     * @param FormatterManager    $formatterManager
     */
    public function __construct(
        TranslatorInterface $translator,
        ConfigManager $configManager,
        ManagerRegistry $doctrine,
        FormatterManager $formatterManager
    ) {
        $this->translator       = $translator;
        $this->configManager    = $configManager;
        $this->doctrine         = $doctrine;
        $this->formatterManager = $formatterManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableDefinitions($entityClass = null)
    {
        if ($entityClass) {
            // process the specified entity only
            return $this->getEntityVariableDefinitions($entityClass);
        }

        // process all entities
        $result    = [];
        $entityIds = $this->getEntityConfigProvider()->getIds();
        foreach ($entityIds as $entityId) {
            $className  = $entityId->getClassName();
            $entityData = $this->getEntityVariableDefinitions($className);
            if (!empty($entityData)) {
                $result[$className] = $entityData;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableGetters($entityClass = null)
    {
        if ($entityClass) {
            // process the specified entity only
            return $this->getEntityVariableGetters($entityClass);
        }

        // process all entities
        $result    = [];
        $entityIds = $this->getEntityConfigProvider()->getIds();
        foreach ($entityIds as $entityId) {
            $className  = $entityId->getClassName();
            $entityData = $this->getEntityVariableGetters($className);
            if (!empty($entityData)) {
                $result[$className] = $entityData;
            }
        }

        return $result;
    }

    /**
     * Return false if entity is new or deleted
     *
     * @param $entityClass
     *
     * @return bool
     */
    protected function isEntityAccessible($entityClass)
    {
        if ($extendConfig = $this->getExtendConfigProvider()->hasConfig($entityClass)) {
            if (!class_exists($entityClass)) {
                return false;
            }

            $extendConfig = $this->getExtendConfigProvider()->getConfig($entityClass);
            if ($extendConfig->is('state', ExtendScope::STATE_NEW) || $extendConfig->is('is_deleted')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $entityClass
     *
     * @return array
     */
    protected function getEntityVariableDefinitions($entityClass)
    {
        $entityClass = ClassUtils::getRealClass($entityClass);
        if (!$this->isEntityAccessible($entityClass)) {
            return [];
        }

        $result = [];

        $em           = $this->doctrine->getManagerForClass($entityClass);
        $metadata     = $em->getClassMetadata($entityClass);
        $reflClass    = new \ReflectionClass($entityClass);
        $fieldConfigs = $this->getEmailConfigProvider()->getConfigs($entityClass);
        foreach ($fieldConfigs as $fieldConfig) {
            if (!$fieldConfig->is('available_in_template')) {
                continue;
            }

            /** @var FieldConfigId $fieldId */
            $fieldId   = $fieldConfig->getId();
            $fieldName = $fieldId->getFieldName();

            list($varName) = $this->getFieldAccessInfo($reflClass, $fieldName);
            if (!$varName) {
                continue;
            }

            $var = [
                'type'  => $fieldId->getFieldType(),
                'label' => $this->translator->trans($this->getFieldLabel($entityClass, $fieldName))
            ];

            if ($metadata->hasAssociation($fieldName)) {
                $targetClass = $metadata->getAssociationTargetClass($fieldName);
                if ($this->getEntityConfigProvider()->hasConfig($targetClass)) {
                    $var['related_entity_name'] = $targetClass;
                }
            }

            $formatters = $this->formatterManager->guessFormatters($fieldId);
            if ($formatters) {
                $var = array_merge($var, $formatters);
            }

            $result[$varName] = $var;
        }

        return $result;
    }

    /**
     * @param string $entityClass
     *
     * @return array
     */
    protected function getEntityVariableGetters($entityClass)
    {
        $entityClass = ClassUtils::getRealClass($entityClass);
        if (!$this->isEntityAccessible($entityClass)) {
            return [];
        }

        $result       = [];
        $reflClass    = new \ReflectionClass($entityClass);
        $fieldConfigs = $this->getEmailConfigProvider()->getConfigs($entityClass);
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

            $resultGetter = $getter;
            $formatters   = $this->formatterManager->guessFormatters($fieldId);
            if ($formatters && count($formatters)) {
                $resultGetter = array_merge(['property_path' => $getter], $formatters);
            }

            $result[$varName] = $resultGetter;
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

        $getter = 'is' . $name;
        if ($reflClass->hasMethod($getter) && $reflClass->getMethod($getter)->isPublic()) {
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
        return $this->getEntityConfigProvider()->getConfig($className, $fieldName)->get('label');
    }

    /**
     * @return ConfigProvider
     */
    protected function getEmailConfigProvider()
    {
        if (!$this->emailConfigProvider) {
            $this->emailConfigProvider = $this->configManager->getProvider('email');
        }

        return $this->emailConfigProvider;
    }

    /**
     * @return ConfigProvider
     */
    protected function getEntityConfigProvider()
    {
        if (!$this->entityConfigProvider) {
            $this->entityConfigProvider = $this->configManager->getProvider('entity');
        }

        return $this->entityConfigProvider;
    }

    /**
     * @return ConfigProvider
     */
    protected function getExtendConfigProvider()
    {
        if (!$this->extendConfigProvider) {
            $this->extendConfigProvider = $this->configManager->getProvider('extend');
        }

        return $this->extendConfigProvider;
    }
}
