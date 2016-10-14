<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\TranslationBundle\Helper\TranslationHelper;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;

class Helper
{
    const TRANSLATION_DOMAIN = 'workflows';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var TranslationHelper */
    protected $translationHelper;

    /** @var TranslationKeyGenerator */
    private $translationKeyGenerator;

    /** @var array */
    protected $translations = [];

    /**
     * @param TranslatorInterface $translator
     * @param TranslationHelper $translationHelper
     * @param TranslationKeyGenerator $translationKeyGenerator
     */
    public function __construct(
        TranslatorInterface $translator,
        TranslationHelper $translationHelper,
        TranslationKeyGenerator $translationKeyGenerator
    ) {
        $this->translator = $translator;
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
