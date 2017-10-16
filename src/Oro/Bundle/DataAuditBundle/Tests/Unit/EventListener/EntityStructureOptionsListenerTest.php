<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataAuditBundle\EventListener\EntityStructureOptionsListener;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;

class EntityStructureOptionsListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AuditConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
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
        $field->expects($this->once())
            ->method('addOption')
            ->with(EntityStructureOptionsListener::OPTION_NAME, $isFieldAuditable);

        $data = $this->createMock(EntityStructure::class);
        $data->expects($this->once())
            ->method('getClassName')
            ->willReturn(\stdClass::class);
        $data->expects($this->once())
            ->method('getFields')
            ->willReturn([$field]);
        $data->expects($this->once())
            ->method('addOption')
            ->with(EntityStructureOptionsListener::OPTION_NAME, $isEntityAuditable);

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
}
