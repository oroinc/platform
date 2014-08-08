<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Util\Inflector;
use Doctrine\Common\Persistence\ManagerRegistry;

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

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param TranslatorInterface $translator
     * @param ConfigProvider      $emailConfigProvider
     * @param ConfigProvider      $entityConfigProvider
     * @param ManagerRegistry     $doctrine
     */
    public function __construct(
        TranslatorInterface $translator,
        ConfigProvider $emailConfigProvider,
        ConfigProvider $entityConfigProvider,
        ManagerRegistry $doctrine
    ) {
        $this->translator           = $translator;
        $this->emailConfigProvider  = $emailConfigProvider;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->doctrine             = $doctrine;
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
        $entityIds = $this->entityConfigProvider->getIds();
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
        $entityIds = $this->entityConfigProvider->getIds();
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
     * @param string $entityClass
     * @return array
     */
    protected function getEntityVariableDefinitions($entityClass)
    {
        $entityClass = ClassUtils::getRealClass($entityClass);
        if (!$this->emailConfigProvider->hasConfig($entityClass)) {
            return [];
        }

        $result = [];

        $em           = $this->doctrine->getManagerForClass($entityClass);
        $metadata     = $em->getClassMetadata($entityClass);
        $reflClass    = new \ReflectionClass($entityClass);
        $fieldConfigs = $this->emailConfigProvider->getConfigs($entityClass);
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
                if ($this->entityConfigProvider->hasConfig($targetClass)) {
                    $var['related_entity_name'] = $targetClass;
                }
            }

            $result[$varName] = $var;
        }

        return $result;
    }

    /**
     * @param string $entityClass
     * @return array
     */
    protected function getEntityVariableGetters($entityClass)
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
        return $this->entityConfigProvider->getConfig($className, $fieldName)->get('label');
    }
}
