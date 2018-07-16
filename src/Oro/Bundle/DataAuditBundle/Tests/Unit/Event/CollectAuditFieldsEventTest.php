<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Event;

use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Event\CollectAuditFieldsEvent;

class CollectAuditFieldsEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters()
    {
        $auditField = new AuditField('field', 'string', 'new', 'old');
        $event = new CollectAuditFieldsEvent('Class', ['changes'], ['field' => $auditField]);
        $this->assertSame('Class', $event->getAuditFieldClass());
        $this->assertSame(['changes'], $event->getChangeSet());
        $this->assertSame(['field' => $auditField], $event->getFields());
    }

    public function testAddField()
    {
        $auditField = new AuditField('field', 'string', 'new', 'old');
        $event = new CollectAuditFieldsEvent('Class', [], []);
        $event->addField('key', $auditField);
        $this->assertSame(['key' => $auditField], $event->getFields());
    }
}
