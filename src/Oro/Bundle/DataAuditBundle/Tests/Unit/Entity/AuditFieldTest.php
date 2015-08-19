<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Entity;

use DateTime;

use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Tests\Unit\Stub\AuditField;
use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;

class AuditFieldTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        AuditFieldTypeRegistry::addType('testingtype', 'testingtype');
    }

    public function teardown()
    {
        AuditFieldTypeRegistry::removeType('testingtype');
    }

    /**
     * @dataProvider provider
     */
    public function testAuditField($field, $dataType, $newValue, $oldValue, $expectedDataType)
    {
        $audit = new Audit();

        $auditField = new AuditField($audit, $field, $dataType, $newValue, $oldValue);
        $this->assertEquals($audit, $auditField->getAudit());
        $this->assertEquals($expectedDataType, $auditField->getDataType());
        $this->assertEquals($field, $auditField->getField());
        $this->assertEquals($newValue, $auditField->getNewValue());
        $this->assertEquals($oldValue, $auditField->getOldValue());
    }

    public function provider()
    {
        return [
            ['field', 'boolean', true, false, 'boolean'],
            ['field', 'smallint', 1, 0, 'integer'],
            ['field', 'integer', 1, 0, 'integer'],
            ['field', 'float', 1.5, 3.2, 'float'],
            ['field', 'decimal', 1.5, 3.2, 'float'],
            ['field', 'text', 'new', 'old', 'text'],
            ['field', 'string', 'new', 'old', 'text'],
            ['field', 'guid', 'new', 'old', 'text'],
            ['field', 'date', new DateTime('2014-01-05'), new DateTime('2014-01-07'), 'date'],
            ['field', 'time', new DateTime('13:22:15'), new DateTime('13:32:15'), 'time'],
            [
                'field',
                'datetime',
                new DateTime('2014-01-05 13:22:15'),
                new DateTime('2014-01-07 13:34:07'),
                'datetime'
            ],
            ['field', 'testingtype', 'old', 'new', 'testingtype'],
        ];
    }
}
