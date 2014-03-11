<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Entity;

use Oro\Bundle\ReminderBundle\Model\ReminderInterval;
use Oro\Bundle\ReminderBundle\Entity\Reminder;

class ReminderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Reminder
     */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new Reminder();
    }

    public function testCreate()
    {
        $this->assertEmpty($this->entity->getId());
        $this->assertEquals(Reminder::STATE_NOT_SENT, $this->entity->getState());
    }

    public function testIntervalAndStartAt()
    {
        $expireAt = new \DateTime('2014-01-15');
        $number = 3;
        $unit = ReminderInterval::UNIT_DAY;
        $interval = new ReminderInterval($number, $unit);

        $this->assertNull($this->entity->getStartAt());
        $this->entity->setExpireAt($expireAt);
        $this->entity->setInterval($interval);
        $this->assertAttributeEquals($number, 'intervalNumber', $this->entity);
        $this->assertAttributeEquals($unit, 'intervalUnit', $this->entity);
        $this->assertEquals(new \DateTime('2014-01-12'), $this->entity->getStartAt());
    }

    public function testPrePersist()
    {
        $this->entity->prePersist();

        $this->assertEquals($this->entity->getCreatedAt()->format('Y-m-d'), date('Y-m-d'));
    }

    public function testPreUpdate()
    {
        $this->entity->preUpdate();

        $this->assertEquals($this->entity->getUpdatedAt()->format('Y-m-d'), date('Y-m-d'));
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
        $getter = $getter ?: 'get' . ucfirst($property);
        $setter = $setter ?: 'set' . ucfirst($property);

        $this->assertEquals($this->entity, $this->entity->$setter($value));
        $this->assertEquals($value, $this->entity->$getter());
    }

    public function settersAndGettersDataProvider()
    {
        return [
            'subject' => [
                'value' => 'value',
                'property' => 'subject',
            ],
            'expireAt' => [
                'value' => new \DateTime(),
                'property' => 'expireAt',
            ],
            'method' => [
                'value' => 'email',
                'property' => 'method',
            ],
            'interval' => [
                'value' => new ReminderInterval(1, ReminderInterval::UNIT_DAY),
                'property' => 'interval',
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
            'owner' => [
                'value' => $this->getMock('Oro\\Bundle\\UserBundle\\Entity\\User'),
                'property' => 'owner',
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
