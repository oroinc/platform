<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\TranslationBundle\Helper\TranslationHelper;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\Translator;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;
use Oro\Bundle\WorkflowBundle\Translation\WorkflowTranslationFieldsIterator;

class WorkflowTranslationHelper
{
    const TRANSLATION_DOMAIN = 'workflows';

    /** @var Translator */
    protected $translator;

    /** @var TranslationManager */
    protected $translationManager;

    /** @var TranslationHelper */
    protected $translationHelper;

    /** @var TranslationKeyGenerator */
    protected $translationKeyGenerator;

    /** @var WorkflowTranslationFieldsIterator */
    protected $translationFieldsIterator;

    /**
     * @param Translator $translator
     * @param TranslationManager $translationManager
     * @param TranslationHelper $translationHelper
     * @param TranslationKeyGenerator $translationKeyGenerator
     * @param WorkflowTranslationFieldsIterator $translationFieldsIterator
     */
    public function __construct(
        Translator $translator,
        TranslationManager $translationManager,
        TranslationHelper $translationHelper,
        TranslationKeyGenerator $translationKeyGenerator,
        WorkflowTranslationFieldsIterator $translationFieldsIterator
    ) {
        $this->translator = $translator;
        $this->translationManager = $translationManager;
        $this->translationHelper = $translationHelper;
        $this->translationKeyGenerator = $translationKeyGenerator;
        $this->translationFieldsIterator = $translationFieldsIterator;
    }

    /**
     * @param string $workflowName
     */
    public function prepareTranslations($workflowName)
    {
        $translationKeySource = new TranslationKeySource(
            new WorkflowTemplate(),
            ['workflow_name' => $workflowName]
        );

        $this->translationHelper->prepareValues(
            $this->translationKeyGenerator->generate($translationKeySource),
            $this->translator->getLocale(),
            self::TRANSLATION_DOMAIN
        );
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function findTranslation($key)
    {
        return $this->translationHelper->findValue($key, $this->translator->getLocale(), self::TRANSLATION_DOMAIN);
    }

    /**
     * @param string $key
     * @return string
     */
    public function getTranslation($key)
    {
        return $this->translationHelper->getValue($key, $this->translator->getLocale(), self::TRANSLATION_DOMAIN);
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function saveTranslation($key, $value)
    {
        $currentLocale = $this->translator->getLocale();
        $this->translationManager->saveValue(
            $key,
            $value,
            $currentLocale,
            self::TRANSLATION_DOMAIN,
            Translation::SCOPE_UI
        );

        if ($currentLocale !== Translation::DEFAULT_LOCALE) {
            $existingValue = $this->translationHelper->findValue(
                $key,
                Translation::DEFAULT_LOCALE,
                self::TRANSLATION_DOMAIN
            );

            if ($existingValue === $key) {
                $this->translationManager->saveValue(
                    $key,
                    $value,
                    Translation::DEFAULT_LOCALE,
                    self::TRANSLATION_DOMAIN,
                    Translation::SCOPE_UI
                );
            }
        }
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
    public function extractTranslations(WorkflowDefinition $definition, $workflowName = null)
    {
        $workflowName = $workflowName ?: $definition->getName();
        $this->prepareTranslations($workflowName);
        $definition->setLabel($this->getTranslation($definition->getLabel()));
        $configuration = $definition->getConfiguration();

        $keys = $this->translationFieldsIterator->iterateConfigTranslationFields($workflowName, $configuration);
        foreach ($keys as &$item) {
            $item = $this->getTranslation($item);
        }
        unset($item);

        $definition->setConfiguration($configuration);
    }
}
