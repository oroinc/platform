<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\PostProcessor;

use Oro\Bundle\ApiBundle\PostProcessor\PostProcessorInterface;
use Oro\Bundle\ApiBundle\PostProcessor\PostProcessorRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class PostProcessorRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var PostProcessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $postProcessor1;

    /** @var PostProcessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $postProcessor2;

    /** @var PostProcessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $postProcessor3;

    /** @var PostProcessorRegistry */
    private $registry;

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

    public function testGetPostProcessorNames()
    {
        self::assertSame(
            ['type1', 'type2'],
            $this->registry->getPostProcessorNames()
        );
    }

    public function testGetPostProcessorWhenItExistsForSpecificRequestType()
    {
        self::assertSame(
            $this->postProcessor2,
            $this->registry->getPostProcessor('type1', new RequestType(['rest', 'json_api']))
        );
    }

    public function testGetPostProcessorWhenItDoesNotExistForSpecificRequestTypeButExistsForAnyRequestType()
    {
        self::assertSame(
            $this->postProcessor1,
            $this->registry->getPostProcessor('type1', new RequestType(['another']))
        );
    }

    public function testGetPostProcessorWhenItDoesNotExistForSpecificRequestTypeAndDoesNotExistForAnyRequestType()
    {
        self::assertNull(
            $this->registry->getPostProcessor('type2', new RequestType(['another']))
        );
    }

    public function testGetPostProcessorForUnknownPostProcessor()
    {
        self::assertNull(
            $this->registry->getPostProcessor('undefined', new RequestType(['rest', 'json_api']))
        );
    }
}
