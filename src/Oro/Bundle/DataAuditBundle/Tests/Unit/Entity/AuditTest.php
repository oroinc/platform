<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Entity;

use DateTime;

use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\UserBundle\Entity\User;

class AuditTest extends \PHPUnit_Framework_TestCase
{
    public function testUser()
    {
        $user  = new User();
        $audit = new Audit();

        $this->assertEmpty($audit->getUser());

        $audit->setUser($user);

        $this->assertNotEmpty($audit->getUser());
    }

    public function testObjectName()
    {
        $audit = new Audit();
        $name  = 'LoggedObject';

        $this->assertEmpty($audit->getObjectName());

        $audit->setObjectName($name);

        $this->assertEquals($name, $audit->getObjectName());
    }

    public function testFieldsShouldBeEmptyWhenNewInstanceIsCreated()
    {
        $audit = new Audit();
        $this->assertEmpty($audit->getFields());
    }

    public function testCreateFieldShouldAddNewFieldToAudit()
    {
        $audit = new Audit();
        $audit->createField('field', 'integer', 1, 0);

        $this->assertCount(1, $audit->getFields());
        $field = $audit->getFields()->first();
        $this->assertEquals('integer', $field->getDataType());
        $this->assertEquals(1, $field->getNewValue());
        $this->assertEquals(0, $field->getOldValue());
    }

    public function testGetDataShouldRetrieveOldFormadUsingFields()
    {
        $oldDate = new DateTime();
        $newDate = new DateTime();

        $audit = new Audit();
        $audit->createField('field', 'integer', 1, 0);
        $audit->createField('field2', 'string', 'new_', '_old');
        $audit->createField('field3', 'date', $newDate, $oldDate);
        $audit->createField('field4', 'datetime', $newDate, $oldDate);

        $this->assertEquals(
            [
                'field' => [
                    'new' => 1,
                    'old' => 0,
                ],
                'field2' => [
                    'new' => 'new_',
                    'old' => '_old',
                ],
                'field3' => [
                    'new' => [
                        'value' => $newDate,
                        'type'  => 'date',
                    ],
                    'old' => [
                        'value' => $oldDate,
                        'type'  => 'date',
                    ],
                ],
                'field4' => [
                    'new' => [
                        'value' => $newDate,
                        'type'  => 'datetime',
                    ],
                    'old' => [
                        'value' => $oldDate,
                        'type'  => 'datetime',
                    ],
                ],
            ],
            $audit->getData()
        );
    }
}
