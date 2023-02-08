<?php

namespace Oro\Bundle\ApiBundle\Config\Extension;

use Oro\Bundle\ApiBundle\PostProcessor\PostProcessorRegistry;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Adds validation that a specified post processor exists.
 */
class PostProcessorConfigExtension extends AbstractConfigExtension
{
    private PostProcessorRegistry $postProcessorRegistry;

    public function __construct(PostProcessorRegistry $postProcessorRegistry)
    {
        $this->postProcessorRegistry = $postProcessorRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigureCallbacks(): array
    {
        return [
            'entities.entity.field'                 => function (NodeBuilder $node) {
                $this->addValidationOfPostProcessorType($node);
            },
            'actions.action.field'                  => function (NodeBuilder $node) {
                $this->addValidationOfPostProcessorType($node);
            },
            'subresources.subresource.action.field' => function (NodeBuilder $node) {
                $this->addValidationOfPostProcessorType($node);
            }
        ];
    }

    private function addValidationOfPostProcessorType(NodeBuilder $node): void
    {
        $node->end()->validate()
            ->always(function ($v) {
                if (!empty($v[ConfigUtil::POST_PROCESSOR])) {
                    $postProcessorNames = $this->postProcessorRegistry->getPostProcessorNames();
                    if (!\in_array($v[ConfigUtil::POST_PROCESSOR], $postProcessorNames, true)) {
                        throw new \InvalidArgumentException(sprintf(
                            'The post processor "%s" is unknown. Known post processors: "%s".',
                            $v[ConfigUtil::POST_PROCESSOR],
                            implode(', ', $postProcessorNames)
                        ));
                    }
                }

                return $v;
            });
    }
}
