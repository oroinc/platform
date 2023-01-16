<?php

namespace Oro\Bundle\ApiBundle\PostProcessor;

use Oro\Component\EntitySerializer\DataTransformerInterface;

/**
 * Adapts a post processor to a data transformer.
 */
class PostProcessingDataTransformer implements DataTransformerInterface
{
    private PostProcessorInterface $postProcessor;
    private array $postProcessorOptions;

    public function __construct(PostProcessorInterface $postProcessor, array $postProcessorOptions)
    {
        $this->postProcessor = $postProcessor;
        $this->postProcessorOptions = $postProcessorOptions;
    }

    /**
     * {@inheritDoc}
     */
    public function transform(mixed $value, array $config, array $context): mixed
    {
        return $this->postProcessor->process($value, $this->postProcessorOptions);
    }
}
