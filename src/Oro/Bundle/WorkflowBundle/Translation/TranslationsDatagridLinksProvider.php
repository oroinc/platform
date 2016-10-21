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
        if (0 === count($this->languageProvider->getAvailableLanguages())) {
            return [];
        }

        $configuration = $definition->getConfiguration();
        $translateLinks['label'] = $this->routeHelper->generate(['key' => $definition->getLabel()]);

        $linksData = [
            WorkflowConfiguration::NODE_STEPS => ['label'],
            WorkflowConfiguration::NODE_TRANSITIONS => ['label', 'message'],
            WorkflowConfiguration::NODE_ATTRIBUTES => ['label'],
        ];

        foreach ($linksData as $node => $attributes) {
            $translateLinks[$node] = $this->getWorkflowNodeTranslateLinks($configuration, $node, $attributes);
        }

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
                $translateLinks[$name][$attribute] = $this->routeHelper->generate(['key' => $item[$attribute]]);
            }
        }

        return $translateLinks;
    }
}
