<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Model;

use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;

class AuditFieldTypeRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testAuditFieldTypeRegistry(): void
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

    public function testAddingTypeWithExistingAuditType(): void
    {
        AuditFieldTypeRegistry::addType('newtype_exist', 'simplearray');
        $this->assertTrue(AuditFieldTypeRegistry::hasType('newtype_exist'));
    }

    public function testAddingTypeWithNotExistingAuditType(): void
    {
        AuditFieldTypeRegistry::addType('newtype_not_exist', 'testingtype');
        $this->assertTrue(AuditFieldTypeRegistry::hasType('newtype_not_exist'));
    }
}
