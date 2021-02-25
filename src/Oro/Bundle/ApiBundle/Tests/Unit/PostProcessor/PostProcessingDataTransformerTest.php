<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\PostProcessor;

use Oro\Bundle\ApiBundle\PostProcessor\PostProcessingDataTransformer;
use Oro\Bundle\ApiBundle\PostProcessor\PostProcessorInterface;

class PostProcessingDataTransformerTest extends \PHPUnit\Framework\TestCase
{
    public function testTransform()
    {
        $postProcessor = $this->createMock(PostProcessorInterface::class);
        $postProcessorOptions = ['key' => 'value'];

        $value = 'test';
        $transformedValue = 'transformed';

        $postProcessor->expects(self::once())
            ->method('process')
            ->with($value, $postProcessorOptions)
            ->willReturn($transformedValue);

        $postProcessingDataTransformer = new PostProcessingDataTransformer($postProcessor, $postProcessorOptions);
        self::assertSame(
            $transformedValue,
            $postProcessingDataTransformer->transform(
                $value,
                ['config_key' => 'config_value'],
                ['context_key' => 'context_value']
            )
        );
    }
}
