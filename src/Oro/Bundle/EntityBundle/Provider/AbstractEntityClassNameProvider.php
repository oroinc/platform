<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Inflector\Inflector;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

abstract class AbstractEntityClassNameProvider
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param ConfigManager       $configManager
     * @param TranslatorInterface $translator
     */
    public function __construct(ConfigManager $configManager, TranslatorInterface $translator)
    {
        $this->configManager = $configManager;
        $this->translator = $translator;
    }

    /**
     * @param string $entityClass
     * @param bool   $isPlural
     *
     * @return string|null
     */
    protected function getName($entityClass, $isPlural = false)
    {
        $labelName = $this->getLabelName($entityClass, $isPlural);
        if ($labelName) {
            $translated = $this->translator->trans($labelName, [], null, 'en');
            if ($translated && $translated !== $labelName) {
                return $translated;
            }
        }

        return null;
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     * @param bool   $isPlural
     *
     * @return string|null
     */
    protected function getFieldName($entityClass, $fieldName, $isPlural = false)
    {
        $entityClassName = $this->getName($entityClass);
        if (!$entityClassName) {
            return null;
        }

        $labelName = $this->getFieldLabelName($entityClass, $fieldName);
        if ($labelName) {
            $translated = $this->translator->trans($labelName, [], null, 'en');
            if ($translated && $translated !== $labelName) {
                return $entityClassName . ' ' . ($isPlural ? Inflector::pluralize($translated) : $translated);
            }
        }

        return null;
    }

    /**
     * @param string $entityClass
     * @param bool   $isPlural
     *
     * @return string|null
     */
    protected function getLabelName($entityClass, $isPlural = false)
    {
        if (!$this->configManager->hasConfig($entityClass)) {
            return null;
        }

        return $this->configManager->getEntityConfig('entity', $entityClass)
            ->get($isPlural ? 'plural_label' : 'label');
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     *
     * @return string|null
     */
    protected function getFieldLabelName($entityClass, $fieldName)
    {
        if (!$this->configManager->hasConfig($entityClass, $fieldName)) {
            return null;
        }

        return $this->configManager->getFieldConfig('entity', $entityClass, $fieldName)
            ->get('label');
    }
}
