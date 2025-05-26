<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\PostProcessor;

use Oro\Bundle\ApiBundle\PostProcessor\PostProcessorInterface;
use Oro\Bundle\ApiBundle\PostProcessor\PostProcessorRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PostProcessorRegistryTest extends TestCase
{
    private PostProcessorInterface&MockObject $postProcessor1;
    private PostProcessorInterface&MockObject $postProcessor2;
    private PostProcessorInterface&MockObject $postProcessor3;
    private PostProcessorRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->postProcessor1 = $this->createMock(PostProcessorInterface::class);
        $this->postProcessor2 = $this->createMock(PostProcessorInterface::class);
        $this->postProcessor3 = $this->createMock(PostProcessorInterface::class);

        $this->registry = new PostProcessorRegistry(
            [
                'type1' => [
                    ['post_processor_2', 'rest'],
                    ['post_processor_1', null]
                ],
                'type2' => [
                    ['post_processor_3', 'rest']
                ]
            ],
            TestContainerBuilder::create()
                ->add('post_processor_1', $this->postProcessor1)
                ->add('post_processor_2', $this->postProcessor2)
                ->add('post_processor_3', $this->postProcessor3)
                ->getContainer($this),
            new RequestExpressionMatcher()
        );
    }

    public function testGetPostProcessorNames(): void
    {
        self::assertSame(
            ['type1', 'type2'],
            $this->registry->getPostProcessorNames()
        );
    }

    public function testGetPostProcessorWhenItExistsForSpecificRequestType(): void
    {
        self::assertSame(
            $this->postProcessor2,
            $this->registry->getPostProcessor('type1', new RequestType(['rest', 'json_api']))
        );
    }

    public function testGetPostProcessorWhenItDoesNotExistForSpecificRequestTypeButExistsForAnyRequestType(): void
    {
        self::assertSame(
            $this->postProcessor1,
            $this->registry->getPostProcessor('type1', new RequestType(['another']))
        );
    }

    public function testGetPostProcessorWhenItDoesNotExistForSpecificRequestTypeAndDoesNotExistForAnyRequestType(): void
    {
        self::assertNull(
            $this->registry->getPostProcessor('type2', new RequestType(['another']))
        );
    }

    public function testGetPostProcessorForUnknownPostProcessor(): void
    {
        self::assertNull(
            $this->registry->getPostProcessor('undefined', new RequestType(['rest', 'json_api']))
        );
    }
}
