<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataAuditBundle\EventListener\EntityStructureOptionsListener;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Helper\UnidirectionalFieldHelper;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;

class EntityStructureOptionsListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuditConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $auditConfigProvider;

    /** @var EntityStructureOptionsListener */
    protected $listener;

    public function setUp()
    {
        $this->auditConfigProvider = $this->createMock(AuditConfigProvider::class);
        $this->listener = new EntityStructureOptionsListener($this->auditConfigProvider);
    }

    /**
     * @dataProvider onOptionsRequestDataProvider
     *
     * @param bool $isEntityAuditable
     * @param bool $isFieldAuditable
     */
    public function testOnOptionsRequest($isEntityAuditable, $isFieldAuditable)
    {
        $field = $this->createMock(EntityFieldStructure::class);
        $field->expects($this->once())
            ->method('getName')
            ->willReturn('test_field');
        $field->expects($this->exactly((int) $isFieldAuditable))
            ->method('addOption')
            ->with('auditable', $isFieldAuditable);

        $data = $this->createMock(EntityStructure::class);
        $data->expects($this->once())
            ->method('getClassName')
            ->willReturn(\stdClass::class);
        $data->expects($this->once())
            ->method('getFields')
            ->willReturn([$field]);
        $data->expects($this->exactly((int) $isEntityAuditable))
            ->method('addOption')
            ->with('auditable', $isEntityAuditable);

        $this->auditConfigProvider
            ->expects($this->once())
            ->method('isAuditableEntity')
            ->with(\stdClass::class)
            ->willReturn($isEntityAuditable);
        $this->auditConfigProvider
            ->expects($this->once())
            ->method('isAuditableField')
            ->with(\stdClass::class, 'test_field')
            ->willReturn($isFieldAuditable);

        /** @var EntityStructureOptionsEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(EntityStructureOptionsEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn([$data]);
        $event->expects($this->once())
            ->method('setData');

        $this->listener->onOptionsRequest($event);
    }

    /**
     * @return array
     */
    public function onOptionsRequestDataProvider()
    {
        return [
            'both auditable' => [
                'isEntityAuditable' => true,
                'isFieldAuditable' => true,
            ],
            'both not auditable' => [
                'isEntityAuditable' => false,
                'isFieldAuditable' => false,
            ],
            'entity auditable' => [
                'isEntityAuditable' => true,
                'isFieldAuditable' => false,
            ],
            'field auditable' => [
                'isEntityAuditable' => false,
                'isFieldAuditable' => true,
            ],
        ];
    }

    public function testOnOptionsRequestUnidirectional()
    {
        $fieldName = sprintf('class%sfield', UnidirectionalFieldHelper::DELIMITER);
        $field = $this->createMock(EntityFieldStructure::class);
        $field->expects($this->once())
            ->method('getName')
            ->willReturn($fieldName);
        $field->expects($this->once())
            ->method('addOption')
            ->with('auditable', true);

        $data = $this->createMock(EntityStructure::class);
        $data->expects($this->once())
            ->method('getClassName')
            ->willReturn(\stdClass::class);
        $data->expects($this->once())
            ->method('getFields')
            ->willReturn([$field]);
        $data->expects($this->once())
            ->method('addOption')
            ->with('auditable', true);

        $this->auditConfigProvider
            ->expects($this->once())
            ->method('isAuditableEntity')
            ->with(\stdClass::class)
            ->willReturn(true);
        $this->auditConfigProvider
            ->expects($this->once())
            ->method('isAuditableField')
            ->with('class', 'field')
            ->willReturn(true);

        /** @var EntityStructureOptionsEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(EntityStructureOptionsEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn([$data]);
        $event->expects($this->once())
            ->method('setData');

        $this->listener->onOptionsRequest($event);
    }
}
