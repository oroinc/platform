<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Helper\TranslationHelper;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\Translator;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;
use Oro\Bundle\WorkflowBundle\Translation\WorkflowTranslationFieldsIterator;

class WorkflowTranslationHelper
{
    const TRANSLATION_DOMAIN = 'workflows';

    /** @var Translator */
    private $translator;

    /** @var TranslationManager */
    private $translationManager;

    /** @var TranslationHelper */
    private $translationHelper;

    /** @var WorkflowTranslationFieldsIterator */
    private $translationFieldsIterator;

    /** @var TranslationKeyGenerator */
    private $translationKeyGenerator;

    /** @var array */
    private $values = [];

    /**
     * @param Translator $translator
     * @param TranslationManager $translationManager
     * @param TranslationHelper $translationHelper
     */
    public function __construct(
        Translator $translator,
        TranslationManager $translationManager,
        TranslationHelper $translationHelper
    ) {
        $this->translator = $translator;
        $this->translationManager = $translationManager;
        $this->translationHelper = $translationHelper;
    }

    /**
     * @param string $workflowName
     * @param string $locale
     * @return array
     */
    public function findWorkflowTranslations($workflowName, $locale)
    {
        $keyPrefix = $this->getTranslationKeyGenerator()->generate(
            new TranslationKeySource(new WorkflowTemplate(), ['workflow_name' => $workflowName])
        );

        return $this->translationHelper->findValues($keyPrefix, $locale, self::TRANSLATION_DOMAIN);
    }

    /**
     * @param string $key
     * @param string $workflowName
     * @param string|null $locale
     * @return string
     */
    public function findWorkflowTranslation($key, $workflowName = null, $locale = null)
    {
        $locale = $locale ?: $this->translator->getLocale();

        $cacheKey = sprintf('%s-%s', $locale, $workflowName);

        if (!array_key_exists($cacheKey, $this->values)) {
            $this->values[$cacheKey] = $this->findWorkflowTranslations($workflowName, $locale);
        }

        $result = isset($this->values[$cacheKey][$key]) ? $this->values[$cacheKey][$key] : null;

        if (!$result && $locale !== Translation::DEFAULT_LOCALE) {
            $result = $this->findWorkflowTranslation($key, $workflowName, Translation::DEFAULT_LOCALE);
        }

        return $result ?: $key;
    }

    /**
     * @param string $key
     * @param string|null $locale
     * @return string
     */
    public function findTranslation($key, $locale = null)
    {
        $locale = $locale ?: $this->translator->getLocale();

        $result = $this->translationHelper->findValue($key, $locale, self::TRANSLATION_DOMAIN);

        if (!$result && $locale !== Translation::DEFAULT_LOCALE) {
            $result = $this->findTranslation($key, Translation::DEFAULT_LOCALE);
        }

        return $result ?: $key;
    }

    /**
     * @param string $key
     * @param string $value
     *
     */
    public function saveTranslation($key, $value)
    {
        $this->translationManager->saveValue($key, $value, $this->translator->getLocale(), self::TRANSLATION_DOMAIN);
    }

    /**
     * @param string $key
     */
    public function ensureTranslationKey($key)
    {
        $this->translationManager->findTranslationKey($key, self::TRANSLATION_DOMAIN);
    }

    /**
     * @param string $key
     */
    public function removeTranslationKey($key)
    {
        $this->translationManager->removeTranslationKey($key, self::TRANSLATION_DOMAIN);
    }

    /**
     * @param WorkflowDefinition $definition
     * @param string $workflowName
     */
    public function translateWorkflowDefinitionFields(WorkflowDefinition $definition, $workflowName = null)
    {
        $workflowName = $workflowName ?: $definition->getName();

        $definition->setLabel($this->findWorkflowTranslation($definition->getLabel(), $workflowName));
        $configuration = $definition->getConfiguration();

        $keys = $this->getWorkflowTranslationFieldsIterator()
            ->iterateConfigTranslationFields($workflowName, $configuration);
        foreach ($keys as &$item) {
            $item = $this->findWorkflowTranslation($item, $workflowName);
        }
        unset($item);

        // TODO: will be removed in scope https://magecore.atlassian.net/browse/BAP-12019
        foreach ($definition->getSteps() as $step) {
            $step->setLabel($this->findWorkflowTranslation($step->getLabel(), $workflowName));
        }

        $definition->setConfiguration($configuration);
    }

    /**
     * @return WorkflowTranslationFieldsIterator
     */
    private function getWorkflowTranslationFieldsIterator()
    {
        if (null === $this->translationFieldsIterator) {
            $this->translationFieldsIterator = new WorkflowTranslationFieldsIterator(
                $this->getTranslationKeyGenerator()
            );
        }

        return $this->translationFieldsIterator;
    }

    /**
     * @return TranslationKeyGenerator
     */
    private function getTranslationKeyGenerator()
    {
        if (null === $this->translationKeyGenerator) {
            $this->translationKeyGenerator = new TranslationKeyGenerator();
        }

        return $this->translationKeyGenerator;
    }
}
