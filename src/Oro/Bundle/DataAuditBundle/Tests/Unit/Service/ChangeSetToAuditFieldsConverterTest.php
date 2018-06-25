<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Service;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataAuditBundle\Event\CollectAuditFieldsEvent;
use Oro\Bundle\DataAuditBundle\Loggable\AuditEntityMapper;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Provider\EntityNameProvider;
use Oro\Bundle\DataAuditBundle\Service\ChangeSetToAuditFieldsConverter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ChangeSetToAuditFieldsConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AuditEntityMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $auditEntityMapper;

    /**
     * @var AuditConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $auditConfigProvider;

    /**
     * @var EntityNameProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityNameProvider;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventDispatcher;

    /**
     * @var ChangeSetToAuditFieldsConverter
     */
    private $converter;

    protected function setUp()
    {
        $this->auditEntityMapper = $this->createMock(AuditEntityMapper::class);
        $this->auditConfigProvider = $this->createMock(AuditConfigProvider::class);
        $this->entityNameProvider = $this->createMock(EntityNameProvider::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->converter = new ChangeSetToAuditFieldsConverter(
            $this->auditEntityMapper,
            $this->auditConfigProvider,
            $this->entityNameProvider
        );
    }

    public function testConvertWithEmptyChangeSetWhenNoEventDispatcher()
    {
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->createMock(ClassMetadata::class);
        $this->auditEntityMapper->expects($this->never())
            ->method('getAuditEntryFieldClassForAuditEntry');
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');
        $this->converter->convert('Entry', $classMetadata, []);
    }

    public function testConvertWithEmptyChangeSet()
    {
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->createMock(ClassMetadata::class);
        $this->auditEntityMapper->expects($this->once())
            ->method('getAuditEntryFieldClassForAuditEntry')
            ->with('Entry')
            ->willReturn('EntryField');
        $event = new CollectAuditFieldsEvent('EntryField', [], []);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(CollectAuditFieldsEvent::NAME, $event);
        $this->converter->setEventDispatcher($this->eventDispatcher);
        $this->converter->convert('Entry', $classMetadata, []);
    }
}
