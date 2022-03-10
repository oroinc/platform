<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\UpdateItem;

use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\ExecuteTargetProcessor;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

class ExecuteTargetProcessorTest extends BatchUpdateItemProcessorTestCase
{
    /** @var ExecuteTargetProcessor */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new ExecuteTargetProcessor();
    }

    private function getTargetContext(): Context
    {
        return new Context(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
    }

    public function testProcessWhenNoTargetProcessor()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The target processor is not defined.');

        $this->processor->process($this->context);
    }

    public function testProcessWhenNoTargetContext()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The target context is not defined.');

        $this->context->setTargetProcessor($this->createMock(ActionProcessorInterface::class));
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoFirstGroupInTargetContext()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The target first group is not defined.');

        $targetContext = $this->getTargetContext();

        $this->context->setTargetProcessor($this->createMock(ActionProcessorInterface::class));
        $this->context->setTargetContext($targetContext);
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoLastGroupInTargetContext()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The target last group is not defined.');

        $targetContext = $this->getTargetContext();
        $targetContext->setFirstGroup('group1');

        $this->context->setTargetProcessor($this->createMock(ActionProcessorInterface::class));
        $this->context->setTargetContext($targetContext);
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoErrorsOccurredWhenProcessingTargetProcessor()
    {
        $targetProcessor = $this->createMock(ActionProcessorInterface::class);

        $targetContext = $this->getTargetContext();
        $targetContext->setFirstGroup('group1');
        $targetContext->setLastGroup('group2');

        $targetProcessor->expects(self::once())
            ->method('process');

        $this->context->setTargetProcessor($targetProcessor);
        $this->context->setTargetContext($targetContext);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenSomeErrorsOccurredWhenProcessingTargetProcessor()
    {
        $targetProcessor = $this->createMock(ActionProcessorInterface::class);

        $targetContext = $this->getTargetContext();
        $targetContext->setFirstGroup('group1');
        $targetContext->setLastGroup('group2');

        $error = Error::create('some error');

        $targetProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (Context $context) use ($error) {
                $context->addError($error);
            });

        $this->context->setTargetProcessor($targetProcessor);
        $this->context->setTargetContext($targetContext);
        $this->processor->process($this->context);

        self::assertFalse($targetContext->hasErrors());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals([$error], $this->context->getErrors());
    }
}
