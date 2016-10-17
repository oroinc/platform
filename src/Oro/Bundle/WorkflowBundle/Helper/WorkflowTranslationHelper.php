<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Oro\Bundle\TranslationBundle\Helper\TranslationHelper;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;


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

    /** @var array */
    protected $translations = [];

    /**
     * @param Translator $translator
     * @param TranslationManager $translationManager
     */
    public function __construct(
        Translator $translator,
        TranslationManager $translationManager,
        TranslationHelper $translationHelper,
        TranslationKeyGenerator $translationKeyGenerator
    ) {
        $this->translator = $translator;
        $this->translationManager = $translationManager;
        $this->translationHelper = $translationHelper;
        $this->translationKeyGenerator = $translationKeyGenerator;
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
    public function extractTranslations(WorkflowDefinition $definition, $workflowName = null)
    {
        $this->prepareTranslations($workflowName ?: $definition->getName());

        $definition->setLabel($this->getTranslation($definition->getLabel()));

        $configuration = $definition->getConfiguration();

        foreach ($configuration['steps'] as &$value) {
            $value['label'] = $this->getTranslation($value['label']);
        }

        foreach ($configuration['transitions'] as &$value) {
            $value['label'] = $this->getTranslation($value['label']);
            $value['message'] = $this->getTranslation($value['message']);
        }

        foreach ($configuration['attributes'] as &$value) {
            $value['label'] = $this->getTranslation($value['label']);
        }

        foreach ($definition->getSteps() as $step) {
            $step->setLabel($this->getTranslation($step->getLabel()));
        }

        $definition->setConfiguration($configuration);
    }
}
