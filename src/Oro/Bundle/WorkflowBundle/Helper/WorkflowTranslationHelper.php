<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

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
    private $translator;

    /** @var TranslationManager */
    private $translationManager;

    /** @var TranslationHelper */
    private $translationHelper;

    /** @var WorkflowTranslationFieldsIterator */
    private $translationFieldsIterator;

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
        $workflowName = $workflowName ?: $definition->getName();
        $this->prepareTranslations($workflowName);
        $definition->setLabel($this->getTranslation($definition->getLabel()));
        $configuration = $definition->getConfiguration();

        $keys = $this->getWorkflowTranslationFieldsIterator()
            ->iterateConfigTranslationFields($workflowName, $configuration);
        foreach ($keys as &$item) {
            $item = $this->getTranslation($item);
        }
        unset($item);

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
