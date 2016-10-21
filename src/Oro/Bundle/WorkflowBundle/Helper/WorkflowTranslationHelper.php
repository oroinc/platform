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
use Oro\Bundle\WorkflowBundle\Translation\WorkflowDefinitionTranslationFieldsIterator;

class WorkflowTranslationHelper
{
    const TRANSLATION_DOMAIN = 'workflows';

    /** @var Translator */
    private $translator;

    /** @var TranslationManager */
    private $translationManager;

    /** @var TranslationHelper */
    private $translationHelper;

    /** @var TranslationKeyGenerator */
    private $translationKeyGenerator;

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
     */
    public function prepareTranslations($workflowName)
    {
        $translationKeySource = new TranslationKeySource(
            new WorkflowTemplate(),
            ['workflow_name' => $workflowName]
        );

        $this->translationHelper->prepareValues(
            $this->getTranslationKeyGenerator()->generate($translationKeySource),
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
     *
     */
    public function saveTranslation($key, $value)
    {
        $currentLocale = $this->translator->getLocale();
        $this->saveValue($key, $value, $currentLocale);

        if ($currentLocale !== Translation::DEFAULT_LOCALE) {
            $existingValue = $this->findValue($key);

            if ($existingValue === $key) {
                $this->saveValue($key, $value);
            }
        }
    }

    /**
     * @param string $key
     * @param string $locale
     * @return string
     */
    private function findValue($key, $locale = Translation::DEFAULT_LOCALE)
    {
        return $this->translationHelper->findValue($key, $locale, self::TRANSLATION_DOMAIN);
    }

    /**
     * @param string $key
     * @param string $value
     * @param string $locale
     */
    private function saveValue($key, $value, $locale = Translation::DEFAULT_LOCALE)
    {
        $this->translationManager->saveValue($key, $value, $locale, self::TRANSLATION_DOMAIN, Translation::SCOPE_UI);
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
     */
    public function extractTranslations(WorkflowDefinition $definition)
    {
        $iterator = new WorkflowDefinitionTranslationFieldsIterator($definition);

        $this->prepareTranslations($definition->getName());

        foreach ($iterator as $item) {
            $iterator->writeCurrent($this->getTranslation($item));
        }
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
