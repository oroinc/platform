<?php

namespace Oro\Bundle\ApiBundle\Config\Extension;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Adds validation that a TWIG template is specified for the "twig" post processor.
 */
class TwigPostProcessorConfigExtension extends AbstractConfigExtension
{
    /**
     * {@inheritDoc}
     */
    public function getConfigureCallbacks(): array
    {
        return [
            'entities.entity.field'                 => function (NodeBuilder $node) {
                $this->addValidationOfPostProcessorOptions($node);
            },
            'actions.action.field'                  => function (NodeBuilder $node) {
                $this->addValidationOfPostProcessorOptions($node);
            },
            'subresources.subresource.action.field' => function (NodeBuilder $node) {
                $this->addValidationOfPostProcessorOptions($node);
            }
        ];
    }

    private function addValidationOfPostProcessorOptions(NodeBuilder $node): void
    {
        $node->end()->validate()
            ->ifTrue(function ($v) {
                return
                    isset($v[ConfigUtil::POST_PROCESSOR])
                    && 'twig' === $v[ConfigUtil::POST_PROCESSOR]
                    && empty($v[ConfigUtil::POST_PROCESSOR_OPTIONS]['template']);
            })
            ->thenInvalid(sprintf(
                'The "template" option is required for the "twig" post processor. Add it to the "%s".',
                ConfigUtil::POST_PROCESSOR_OPTIONS
            ));
    }
}
