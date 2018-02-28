<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Helper\TranslationHelper;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
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
     *
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
     *
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

        if (!$result && $locale !== Translator::DEFAULT_LOCALE) {
            $result = $this->findWorkflowTranslation($key, $workflowName, Translator::DEFAULT_LOCALE);
        }

        return $result ?: $key;
    }

    /**
     * @param string $key
     * @param string|null $locale
     *
     * @return string|null
     */
    public function findTranslation($key, $locale = null)
    {
        if (!$locale) {
            $locale = $this->translator->getLocale();
        }

        $result = $this->translationHelper->findValue($key, $locale, self::TRANSLATION_DOMAIN);

        if (!$result && $locale !== Translator::DEFAULT_LOCALE) {
            $result = $this->findTranslation($key, Translator::DEFAULT_LOCALE);
        }

        return $result ?: $key;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function saveTranslation($key, $value)
    {
        $this->saveTranslationValue($key, $value);
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function saveTranslationAsSystem($key, $value)
    {
        $this->saveTranslationValue($key, $value, Translation::SCOPE_SYSTEM);
    }

    public function flushTranslations()
    {
        $this->translationManager->flush();
    }

    /**
     * @param string $key
     * @param string $locale
     *
     * @return null|string
     */
    public function findValue($key, $locale = Translator::DEFAULT_LOCALE)
    {
        return $this->translationHelper->findValue($key, $locale, self::TRANSLATION_DOMAIN);
    }

    /**
     * @param string $key
     * @param string $value
     * @param int $scope
     */
    private function saveTranslationValue($key, $value, $scope = Translation::SCOPE_UI)
    {
        $currentLocale = $this->translator->getLocale();
        $this->saveValue($key, $value, $currentLocale, $scope);

        if ($currentLocale !== Translator::DEFAULT_LOCALE && null === $this->findValue($key)) {
            $this->saveValue($key, $value, Translator::DEFAULT_LOCALE, $scope);
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @param string $locale
     * @param int $scope
     */
    private function saveValue($key, $value, $locale = Translator::DEFAULT_LOCALE, $scope = Translation::SCOPE_UI)
    {
        $this->translationManager->saveTranslation(
            $key,
            $value,
            $locale,
            self::TRANSLATION_DOMAIN,
            $scope
        );
    }

    /**
     * @param WorkflowDefinition $definition
     *
     * @return array
     */
    public function generateDefinitionTranslationKeys(WorkflowDefinition $definition)
    {
        $config = $definition->getConfiguration();

        $keys = [
            $definition->getLabel(),
        ];

        foreach ($config[WorkflowConfiguration::NODE_STEPS] as $item) {
            $keys[] = $item['label'];
        }

        foreach ($config[WorkflowConfiguration::NODE_ATTRIBUTES] as $item) {
            $keys[] = $item['label'];
        }

        foreach ($config[WorkflowConfiguration::NODE_TRANSITIONS] as $item) {
            $keys[] = $item['label'];
            $keys[] = $item['button_label'];
            $keys[] = $item['button_title'];
            $keys[] = $item['message'];
        }

        if (isset($config[WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS])) {
            $variableDefinitions = $config[WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS];
            foreach ($variableDefinitions[WorkflowConfiguration::NODE_VARIABLES] as $item) {
                $keys[] = $item['label'];
                if (isset($item['options']['form_options']['tooltip'])) {
                    $keys[] = $item['options']['form_options']['tooltip'];
                }
            }
        }

        return $keys;
    }

    /**
     * @param array $keys
     * @param string|null $locale
     * @param string $default
     *
     * @return array
     */
    public function generateDefinitionTranslations(array $keys, $locale = null, $default = '')
    {
        $translator = $this->translator;
        $translations = [];
        $domain = WorkflowTranslationHelper::TRANSLATION_DOMAIN;
        $locale = (null === $locale) ? $translator->getLocale() : $locale;

        foreach ($keys as $key) {
            if ($translator->hasTrans($key, $domain, $locale)) {
                $translation = $translator->trans($key, [], $domain, $locale);
            } elseif ($translator->hasTrans($key, $domain, Translator::DEFAULT_LOCALE)) {
                $translation = $translator->trans($key, [], $domain, Translator::DEFAULT_LOCALE);
            } else {
                $translation = $default;
            }

            $translations[$key] = $translation;
        }

        return $translations;
    }
}
