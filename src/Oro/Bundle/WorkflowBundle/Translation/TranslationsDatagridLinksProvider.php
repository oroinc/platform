<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\TranslationBundle\Helper\TranslationsDatagridRouteHelper;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class TranslationsDatagridLinksProvider
{
    /** @var TranslationsDatagridRouteHelper */
    private $routeHelper;

    /** @var LanguageProvider */
    private $languageProvider;

    /**
     * @param TranslationsDatagridRouteHelper $routeHelper
     * @param LanguageProvider $languageProvider
     */
    public function __construct(TranslationsDatagridRouteHelper $routeHelper, LanguageProvider $languageProvider)
    {
        $this->routeHelper = $routeHelper;
        $this->languageProvider = $languageProvider;
    }

    /**
     * @param WorkflowDefinition $definition
     *
     * @return array
     */
    public function getWorkflowTranslateLinks(WorkflowDefinition $definition)
    {
        // translate links are available only if any language is available for current user
        if (!$definition->getName() || 0 === count($this->languageProvider->getAvailableLanguages())) {
            return [];
        }

        $configuration = $definition->getConfiguration();
        $translateLinks['label'] = $this->routeHelper->generate(['key' => $definition->getLabel()]);

        $linksData = [
            WorkflowConfiguration::NODE_STEPS => ['label'],
            WorkflowConfiguration::NODE_TRANSITIONS => ['label', 'message']
        ];

        foreach ($linksData as $node => $attributes) {
            $translateLinks[$node] = $this->getWorkflowNodeTranslateLinks($configuration, $node, $attributes);
        }

        $translateLinks[WorkflowConfiguration::NODE_ATTRIBUTES] = $this->getTransitionAttributeNodesTranslateLinks(
            $configuration
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
                if (!array_key_exists($attribute, $item)) {
                    continue;
                }
                $translateLinks[$name][$attribute] = $this->routeHelper->generate(['key' => $item[$attribute]]);
            }
        }

        return $translateLinks;
    }

    /**
     * @param array $configuration
     * @return array
     */
    private function getTransitionAttributeNodesTranslateLinks(array $configuration)
    {
        $attributes = [];
        if (!array_key_exists(WorkflowConfiguration::NODE_TRANSITIONS, $configuration)) {
            return $attributes;
        }

        foreach ($configuration[WorkflowConfiguration::NODE_TRANSITIONS] as $transitionName => $transition) {
            if (isset($transition['form_options']['attribute_fields'])) {
                foreach ($transition['form_options']['attribute_fields'] as $attributeName => $attribute) {
                    if (isset($attribute['options']['label'])) {
                        $label = $attribute['options']['label'];

                        $attributes[$attributeName][$transitionName]['label'] =
                            $this->routeHelper->generate(['key' => $label]);
                    }
                }
            }
        }

        return $attributes;
    }
}
