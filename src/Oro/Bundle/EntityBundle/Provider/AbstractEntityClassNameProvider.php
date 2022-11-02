<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Inflector\Inflector;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The base class for services to get human-readable names in English of entity classes.
 */
abstract class AbstractEntityClassNameProvider
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;
    private Inflector $inflector;

    public function __construct(ConfigManager $configManager, TranslatorInterface $translator, Inflector $inflector)
    {
        $this->configManager = $configManager;
        $this->translator = $translator;
        $this->inflector = $inflector;
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
                return $entityClassName . ' ' . ($isPlural ? $this->inflector->pluralize($translated) : $translated);
            }
        }

        return null;
    }

    /**
     * @param string $entityClass
     * @param bool   $isPlural
     *
     * @return string
     */
    protected function getLabelName($entityClass, $isPlural = false): string
    {
        if (!$this->configManager->hasConfig($entityClass)
            || $this->configManager->isHiddenModel($entityClass)
        ) {
            return '';
        }

        return (string) $this->configManager->getEntityConfig('entity', $entityClass)
            ->get($isPlural ? 'plural_label' : 'label');
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     *
     * @return string
     */
    protected function getFieldLabelName($entityClass, $fieldName): string
    {
        if (!$this->configManager->hasConfig($entityClass, $fieldName)
            || $this->configManager->isHiddenModel($entityClass, $fieldName)
        ) {
            return '';
        }

        return (string) $this->configManager->getFieldConfig('entity', $entityClass, $fieldName)
            ->get('label');
    }
}
