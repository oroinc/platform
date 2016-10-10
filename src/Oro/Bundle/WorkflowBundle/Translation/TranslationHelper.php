<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\KeySource\DynamicTranslationKeySource;

class TranslationHelper
{
    const WORKFLOWS_DOMAIN = 'workflows';

    /** @var Translator */
    private $translator;

    /** @var TranslationManager */
    private $translationManager;

    /** @var TranslationKeyGenerator */
    private $translationKeyGenerator;

    /** @var string */
    private $currentLocale;

    /**
     * @param Translator $translator
     * @param TranslationManager $translationManager
     * @param TranslationKeyGenerator $translationKeyGenerator
     */
    public function __construct(
        Translator $translator,
        TranslationManager $translationManager,
        TranslationKeyGenerator $translationKeyGenerator
    ) {
        $this->translator = $translator;
        $this->translationManager = $translationManager;
        $this->translationKeyGenerator = $translationKeyGenerator;
    }

    /**
     * @param TranslationKeyTemplateInterface $template
     * @param string $nodeName
     * @param string $attributeName
     * @param string $optionName
     * @param WorkflowDefinition $definition
     */
    public function updateNodeKeys(
        TranslationKeyTemplateInterface $template,
        $nodeName,
        $attributeName,
        $optionName,
        WorkflowDefinition $definition
    ) {
        $configuration = $definition->getConfiguration();
        if (!array_key_exists($nodeName, $configuration)) {
            $configuration[$nodeName] = [];
        }
        $translationKeySource = new DynamicTranslationKeySource(['workflow_name' => $definition->getName()]);

        foreach ($configuration[$nodeName] as $name => &$itemConfig) {
            $key = $this->generateKey($translationKeySource, $template, [$attributeName => $name]);
            if (array_key_exists($optionName, $itemConfig)) {
                $this->saveTranslation($key, $itemConfig[$optionName]);
            } else {
                $this->translationManager->findTranslationKey($key, self::WORKFLOWS_DOMAIN);
            }

            $itemConfig[$optionName] = $key;
        }

        $definition->setConfiguration($configuration);
    }

    /**
     * @param TranslationKeyTemplateInterface $template
     * @param string $nodeName
     * @param string $attributeName
     * @param WorkflowDefinition $definition
     * @param WorkflowDefinition $previousDefinition
     */
    public function cleanupNodeKeys(
        TranslationKeyTemplateInterface $template,
        $nodeName,
        $attributeName,
        WorkflowDefinition $definition,
        WorkflowDefinition $previousDefinition = null
    ) {
        if (null === $previousDefinition || !array_key_exists($nodeName, $previousDefinition->getConfiguration())) {
            return;
        }
        $configuration = $definition->getConfiguration();
        if (!array_key_exists($nodeName, $configuration)) {
            $configuration[$nodeName] = [];
        }
        $translationKeySource = new DynamicTranslationKeySource(['workflow_name' => $definition->getName()]);

        $configurationOld = $previousDefinition->getConfiguration();
        $removedItemNames = array_diff(
            array_keys($configurationOld[$nodeName]),
            array_keys($configuration[$nodeName])
        );
        foreach ($removedItemNames as $itemName) {
            $key = $this->generateKey($translationKeySource, $template, [$attributeName => $itemName]);
            $this->translationManager->removeTranslationKey($key, self::WORKFLOWS_DOMAIN);
        }
    }

    /**
     * @param $key
     * @param $value
     *
     */
    public function saveTranslation($key, $value)
    {
        if (!$this->currentLocale) {
            $this->currentLocale = $this->translator->getLocale();
        }
        $this->translationManager
            ->saveValue($key, $value, $this->currentLocale, self::WORKFLOWS_DOMAIN);
    }

    /**
     * @param DynamicTranslationKeySource $translationKeySource
     * @param TranslationKeyTemplateInterface $template
     * @param array $data
     *
     * @return string
     */
    public function generateKey(
        DynamicTranslationKeySource $translationKeySource,
        TranslationKeyTemplateInterface $template,
        array $data = []
    ) {
        $translationKeySource->configure($template, $data);

        return $this->translationKeyGenerator->generate($translationKeySource);
    }
}
