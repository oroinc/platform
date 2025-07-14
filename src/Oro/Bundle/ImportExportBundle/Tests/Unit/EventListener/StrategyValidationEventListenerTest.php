<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\EventListener;

use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;
use Oro\Bundle\ImportExportBundle\Event\StrategyValidationEvent;
use Oro\Bundle\ImportExportBundle\EventListener\StrategyValidationEventListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class StrategyValidationEventListenerTest extends TestCase
{
    private ConfigurableTableDataConverter&MockObject $configurableDataConverter;
    private StrategyValidationEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->configurableDataConverter = $this->createMock(ConfigurableTableDataConverter::class);

        $this->listener = new StrategyValidationEventListener($this->configurableDataConverter);
    }

    public function testViolationsEmpty(): void
    {
        $violations = new ConstraintViolationList();
        $event = new StrategyValidationEvent($violations);
        $this->listener->buildErrors($event);

        $this->assertEquals([], $event->getErrors());
    }

    public function testViolationsWithEmptyPath(): void
    {
        $violations = new ConstraintViolationList([$this->getViolation()]);
        $event = new StrategyValidationEvent($violations);
        $this->configurableDataConverter->expects($this->never())
            ->method('getFieldHeaderWithRelation');
        $this->listener->buildErrors($event);

        $this->assertEquals(['testerror'], $event->getErrors());
    }

    public function testViolationsAppendPath(): void
    {
        $violations = new ConstraintViolationList([$this->getViolation('prop')]);
        $event = new StrategyValidationEvent($violations);
        $this->configurableDataConverter->expects($this->once())
            ->method('getFieldHeaderWithRelation')
            ->willReturn(null);
        $this->listener->buildErrors($event);

        $this->assertEquals(['prop: testerror'], $event->getErrors());
    }

    public function testViolationsReplacePathByHeader(): void
    {
        $violations = new ConstraintViolationList([$this->getViolation('prop')]);
        $event = new StrategyValidationEvent($violations);
        $this->configurableDataConverter->expects($this->once())
            ->method('getFieldHeaderWithRelation')
            ->willReturn('header');
        $this->listener->buildErrors($event);

        $this->assertEquals(['header: testerror'], $event->getErrors());
    }

    private function getViolation(?string $propertyPath = null): ConstraintViolation
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
