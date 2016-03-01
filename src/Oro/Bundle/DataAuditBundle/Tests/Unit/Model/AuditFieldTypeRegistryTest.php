<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Model;

use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;

class AuditFieldTypeRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testAuditFieldTypeRegistry()
    {
        $this->assertFalse(AuditFieldTypeRegistry::hasType('newtype'));

        AuditFieldTypeRegistry::addType('newtype', 'newtype_');
        $this->assertTrue(AuditFieldTypeRegistry::hasType('newtype'));

        $this->assertEquals('newtype_', AuditFieldTypeRegistry::getAuditType('newtype'));

        AuditFieldTypeRegistry::overrideType('newtype', 'overridentype');
        $this->assertEquals('overridentype', AuditFieldTypeRegistry::getAuditType('newtype'));

        AuditFieldTypeRegistry::removeType('newtype');
        $this->assertFalse(AuditFieldTypeRegistry::hasType('newtype'));
    }
}
