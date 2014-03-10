<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Entity;

use Oro\Bundle\ReminderBundle\Model\ReminderInterval;
use Oro\Bundle\ReminderBundle\Entity\Reminder;

class ReminderTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $entity = new Reminder();

        $this->assertEmpty($entity->getId());
        $this->assertEquals(Reminder::STATE_NOT_SENT, $entity->getState());
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
            'startAt' => [
                'value' => new \DateTime(),
                'property' => 'startAt',
            ],
            'expireAt' => [
                'value' => new \DateTime(),
                'property' => 'expireAt',
            ],
            'method' => [
                'value' => 'email',
                'property' => 'method',
            ],
            'intervalNumber' => [
                'value' => 5,
                'property' => 'intervalNumber',
            ],
            'intervalUnit' => [
                'value' => ReminderInterval::UNIT_HOUR,
                'property' => 'intervalUnit',
            ],
            'state' => [
                'value' => Reminder::STATE_NOT_SENT,
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
            'recipient' => [
                'value' => $this->getMock('Oro\\Bundle\\UserBundle\\Entity\\User'),
                'property' => 'recipient',
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
        ];
    }
}
