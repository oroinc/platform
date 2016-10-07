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
     * @param WorkflowDefinition $definition
     * @param WorkflowDefinition $previousDefinition
     */
    public function updateNode(
        TranslationKeyTemplateInterface $template,
        $nodeName,
        $attributeName,
        WorkflowDefinition $definition,
        WorkflowDefinition $previousDefinition = null
    ) {
        $configuration = $definition->getConfiguration();
        if (empty($configuration[$nodeName])) {
            return;
        }
        $translationKeySource = new DynamicTranslationKeySource(['workflow_name' => $definition->getName()]);

        foreach ($configuration[$nodeName] as $name => &$itemConfig) {
            if (empty($itemConfig['label'])) {
                continue;
            }
            $key = $this->generateKey($translationKeySource, $template, [$attributeName => $name]);
            $this->saveTranslation($key, $itemConfig['label']);
            $itemConfig['label'] = $key;
        }

        if (null !== $previousDefinition &&
            array_key_exists($nodeName, $previousDefinition->getConfiguration())
        ) {
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
        $definition->setConfiguration($configuration);
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
