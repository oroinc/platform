<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\EventListener;

use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;
use Oro\Bundle\ImportExportBundle\Event\StrategyValidationEvent;
use Oro\Bundle\ImportExportBundle\EventListener\StrategyValidationEventListener;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class StrategyValidationEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigurableTableDataConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $configurableDataConverter;

    /** @var StrategyValidationEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->configurableDataConverter = $this->createMock(ConfigurableTableDataConverter::class);

        $this->listener = new StrategyValidationEventListener($this->configurableDataConverter);
    }

    public function testViolationsEmpty()
    {
        $violations = new ConstraintViolationList();
        $event = new StrategyValidationEvent($violations);
        $this->listener->buildErrors($event);

        $this->assertEquals([], $event->getErrors());
    }

    public function testViolationsWithEmptyPath()
    {
        $violations = new ConstraintViolationList([$this->getViolation()]);
        $event = new StrategyValidationEvent($violations);
        $this->configurableDataConverter->expects($this->never())
            ->method('getFieldHeaderWithRelation');
        $this->listener->buildErrors($event);

        $this->assertEquals(['testerror'], $event->getErrors());
    }

    public function testViolationsAppendPath()
    {
        $violations = new ConstraintViolationList([$this->getViolation('prop')]);
        $event = new StrategyValidationEvent($violations);
        $this->configurableDataConverter->expects($this->once())
            ->method('getFieldHeaderWithRelation')
            ->willReturn(null);
        $this->listener->buildErrors($event);

        $this->assertEquals(['prop: testerror'], $event->getErrors());
    }

    public function testViolationsReplacePathByHeader()
    {
        $violations = new ConstraintViolationList([$this->getViolation('prop')]);
        $event = new StrategyValidationEvent($violations);
        $this->configurableDataConverter->expects($this->once())
            ->method('getFieldHeaderWithRelation')
            ->willReturn('header');
        $this->listener->buildErrors($event);

        $this->assertEquals(['header: testerror'], $event->getErrors());
    }

    private function getViolation(string $propertyPath = null): ConstraintViolation
    {
        return new ConstraintViolation(
            'testerror',
            'test',
            [],
            new \stdClass(),
            $propertyPath,
            'fail'
        );
    }
}
