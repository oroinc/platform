<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Entity;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\ReminderState;

class ReminderTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $entity = new Reminder();

        $this->assertEquals($entity->getState(), new ReminderState());
        $this->assertEmpty($entity->getId());
    }

    public function testPrePersist()
    {
        $entity = new Reminder();
        $entity->prePersist();

        $this->assertEquals($entity->getCreatedAt()->format('Y-m-d'), date('Y-m-d'));
    }

    public function testPreUpdate()
    {
        $entity = new Reminder();
        $entity->preUpdate();

        $this->assertEquals($entity->getUpdatedAt()->format('Y-m-d'), date('Y-m-d'));
    }


    public function testToString()
    {
        $entity = new Reminder();
        $entity->setSubject('subject');

        $this->assertEquals('subject', $entity);
    }

    /**
     * @dataProvider settersAndGettersDataProvider
     */
    public function testSettersAndGetters($value, $property, $getter = null, $setter = null)
    {
        $entity = new Reminder();

        $getter = $getter ?: 'get' . ucfirst($property);
        $setter = $setter ?: 'set' . ucfirst($property);

        $this->assertEquals($entity, $entity->$setter($value));
        $this->assertEquals($value, $entity->$getter());
    }

    public function settersAndGettersDataProvider()
    {
        return [
            'subject' => [
                'value' => 'value',
                'property' => 'subject',
            ],
            'dueDate' => [
                'value' => new \DateTime(),
                'property' => 'dueDate',
            ],
            'reminderInterval' => [
                'value' => 5,
                'property' => 'reminderInterval',
            ],
            'state' => [
                'value' => new ReminderState(['type1', 'type2']),
                'property' => 'state',
            ],
            'relatedEntityId' => [
                'value' => 1,
                'property' => 'relatedEntityId',
            ],
            'relatedEntityClassName' => [
                'value' => 'Namespace\\Entity',
                'property' => 'relatedEntityClassName',
            ],
            'createdAt' => [
                'value' => new \DateTime(),
                'property' => 'createdAt',
            ],
            'updatedAt' => [
                'value' => new \DateTime(),
                'property' => 'updatedAt',
            ],
            'sentAt' => [
                'value' => new \DateTime(),
                'property' => 'sentAt',
            ],
            'isSent' => [
                'value' => true,
                'property' => 'isSent',
                'getter' => 'isSent',
                'setter' => 'setSent',
            ],
        ];
    }
}
