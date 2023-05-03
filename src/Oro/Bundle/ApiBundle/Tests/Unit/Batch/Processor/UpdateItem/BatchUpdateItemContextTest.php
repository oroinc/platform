<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\UpdateItem;

use Oro\Bundle\ApiBundle\Batch\Model\BatchSummary;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\ParameterBagInterface;

class BatchUpdateItemContextTest extends \PHPUnit\Framework\TestCase
{
    private BatchUpdateItemContext $context;

    protected function setUp(): void
    {
        $this->context = new BatchUpdateItemContext();
    }

    public function testClassName()
    {
        self::assertNull($this->context->getClassName());

        $this->context->setClassName('Test\Class');
        self::assertEquals('Test\Class', $this->context->getClassName());
        self::assertEquals('Test\Class', $this->context->get('class'));

        $this->context->setClassName('');
        self::assertSame('', $this->context->getClassName());
        self::assertTrue($this->context->has('class'));
        self::assertSame('', $this->context->get('class'));

        $this->context->setClassName(null);
        self::assertNull($this->context->getClassName());
        self::assertFalse($this->context->has('class'));
    }

    public function testId()
    {
        self::assertNull($this->context->getId());

        $id = 'test';
        $this->context->setId($id);
        self::assertSame($id, $this->context->getId());

        $this->context->setId(null);
        self::assertNull($this->context->getId());
    }

    public function testSummary()
    {
        self::assertNull($this->context->getSummary());

        $summary = new BatchSummary();
        $this->context->setSummary($summary);
        self::assertSame($summary, $this->context->getSummary());

        $this->context->setSummary(null);
        self::assertNull($this->context->getSummary());
    }

    public function testSupportedEntityClasses()
    {
        self::assertSame([], $this->context->getSupportedEntityClasses());

        $this->context->setSupportedEntityClasses(['Test\Class']);
        self::assertSame(['Test\Class'], $this->context->getSupportedEntityClasses());
    }

    public function testRequestData()
    {
        self::assertNull($this->context->getRequestData());

        $this->context->setRequestData(['key' => 'value']);
        self::assertSame(['key' => 'value'], $this->context->getRequestData());

        $this->context->setRequestData(null);
        self::assertNull($this->context->getRequestData());
    }

    public function testTargetAction()
    {
        self::assertNull($this->context->getTargetAction());

        $this->context->setTargetAction('test_action');
        self::assertEquals('test_action', $this->context->getTargetAction());
        self::assertEquals('test_action', $this->context->get('targetAction'));

        $this->context->setTargetAction('');
        self::assertSame('', $this->context->getTargetAction());
        self::assertTrue($this->context->has('targetAction'));
        self::assertSame('', $this->context->get('targetAction'));

        $this->context->setTargetAction(null);
        self::assertNull($this->context->getTargetAction());
        self::assertFalse($this->context->has('targetAction'));
    }

    public function testTargetProcessor()
    {
        self::assertNull($this->context->getTargetProcessor());

        $processor = $this->createMock(ActionProcessorInterface::class);
        $this->context->setTargetProcessor($processor);
        self::assertSame($processor, $this->context->getTargetProcessor());

        $this->context->setTargetProcessor(null);
        self::assertNull($this->context->getTargetProcessor());
    }

    public function testTargetContext()
    {
        self::assertNull($this->context->getTargetContext());

        $context = $this->createMock(Context::class);
        $this->context->setTargetContext($context);
        self::assertSame($context, $this->context->getTargetContext());

        $this->context->setTargetContext(null);
        self::assertNull($this->context->getTargetContext());
    }

    public function testSharedData()
    {
        $sharedData = $this->createMock(ParameterBagInterface::class);

        $this->context->setSharedData($sharedData);
        self::assertSame($sharedData, $this->context->getSharedData());
    }
}
