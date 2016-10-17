<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\TranslationBundle\Helper\TranslationRouteHelper;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class TranslationHelper
{
    const WORKFLOWS_DOMAIN = 'workflows';

    /** @var Translator */
    private $translator;

    /** @var TranslationManager */
    private $translationManager;

    /** @var TranslationRouteHelper */
    private $translationRouteHelper;

    /** @var string */
    private $currentLocale;

    /**
     * @param Translator $translator
     * @param TranslationManager $translationManager
     * @param TranslationRouteHelper $translationRouteHelper
     */
    public function __construct(
        Translator $translator,
        TranslationManager $translationManager,
        TranslationRouteHelper $translationRouteHelper
    ) {
        $this->translator = $translator;
        $this->translationManager = $translationManager;
        $this->translationRouteHelper = $translationRouteHelper;
    }

    /**
     * @param string $key
     * @param string $value
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
     * @param string $key
     */
    public function ensureTranslationKey($key)
    {
        $this->translationManager->findTranslationKey($key, self::WORKFLOWS_DOMAIN);
    }

    /**
     * @param string $key
     */
    public function removeTranslationKey($key)
    {
        $this->translationManager->removeTranslationKey($key, self::WORKFLOWS_DOMAIN);
    }


    /**
     * @param WorkflowDefinition $definition
     *
     * @return array
     */
    public function getWorkflowTranslateLinks(WorkflowDefinition $definition)
    {
        $configuration = $definition->getConfiguration();
        $translateLinks['label'] = $this->translationRouteHelper
            ->generate(['key' => $definition->getLabel()]);
        $translateLinks[WorkflowConfiguration::NODE_STEPS] = $this->getWorkflowNodeTranslateLinks(
            $configuration,
            WorkflowConfiguration::NODE_STEPS,
            ['label']
        );
        $translateLinks[WorkflowConfiguration::NODE_TRANSITIONS] = $this->getWorkflowNodeTranslateLinks(
            $configuration,
            WorkflowConfiguration::NODE_TRANSITIONS,
            ['label', 'message']
        );
        $translateLinks[WorkflowConfiguration::NODE_ATTRIBUTES] = $this->getWorkflowNodeTranslateLinks(
            $configuration,
            WorkflowConfiguration::NODE_ATTRIBUTES,
            ['label']
        );

        return $translateLinks;
    }

    /**
     * @param array $configuration
     * @param string $node
     * @param array $attributes
     *
     * @return array
     */
    private function getWorkflowNodeTranslateLinks(array $configuration, $node, array $attributes)
    {
        $translateLinks = [];
        if (!array_key_exists($node, $configuration)) {
            return $translateLinks;
        }
        foreach ($configuration[$node] as $name => $item) {
            foreach ($attributes as $attribute) {
                $translateLinks[$name][$attribute] = $this->translationRouteHelper
                    ->generate(['key' => $item[$attribute]]);
            }
        }

        return $translateLinks;
    }
}
