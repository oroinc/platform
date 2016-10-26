<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Helper\TranslationHelper;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\Translator;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;

class WorkflowTranslationHelper
{
    const TRANSLATION_DOMAIN = 'workflows';

    /** @var Translator */
    private $translator;

    /** @var TranslationManager */
    private $translationManager;

    /** @var TranslationHelper */
    private $translationHelper;

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
        $generator = new TranslationKeyGenerator();

        $keyPrefix = $generator->generate(
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
    public function findWorkflowTranslation($key, $workflowName, $locale = null)
    {
        if (!$locale) {
            $locale = $this->translator->getLocale();
        }

        $cacheKey = sprintf('%s-%s', $locale, $workflowName);

        if (!array_key_exists($cacheKey, $this->values)) {
            $this->values[$cacheKey] = $this->findWorkflowTranslations($workflowName, $locale);
        }

        $result = null;
        if (isset($this->values[$cacheKey][$key])) {
            $result = $this->values[$cacheKey][$key];
        }

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
     */
    public function saveTranslation($key, $value)
    {
        $currentLocale = $this->translator->getLocale();
        $this->saveValue($key, $value, $currentLocale);

        if ($currentLocale !== Translation::DEFAULT_LOCALE) {
            if (null === ($existingValue = $this->findValue($key))) {
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
}
