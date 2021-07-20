<?php

namespace Oro\Bundle\ApiBundle\PostProcessor;

use Oro\Component\EntitySerializer\DataTransformerInterface;

/**
 * Adapts a post processor to a data transformer.
 */
class PostProcessingDataTransformer implements DataTransformerInterface
{
    /** @var PostProcessorInterface */
    private $postProcessor;

    /** @var array */
    private $postProcessorOptions;

    public function __construct(PostProcessorInterface $postProcessor, array $postProcessorOptions)
    {
        $this->postProcessor = $postProcessor;
        $this->postProcessorOptions = $postProcessorOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value, array $config, array $context)
    {
        return $this->postProcessor->process($value, $this->postProcessorOptions);
    }
}
