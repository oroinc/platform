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
        $this->entity->setSubject('subject');

        $this->assertEquals('subject', (string)$this->entity);
    }

    public function testSetSentState()
    {
        $this->entity->setState(Reminder::STATE_SENT);

        $this->assertEquals(Reminder::STATE_SENT, $this->entity->getState());
        $this->assertInstanceOf('DateTime', $this->entity->getSentAt());
    }

    public function testSetReminderData()
    {
        $expectedSubject = 'subject';
        $expectedExpireAt = new \DateTime();
        $expectedRecipient = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $reminderData = $this->getMock('Oro\Bundle\ReminderBundle\Model\ReminderDataInterface');

        $reminderData->expects($this->once())
            ->method('getSubject')
            ->will($this->returnValue($expectedSubject));

        $reminderData->expects($this->once())
            ->method('getExpireAt')
            ->will($this->returnValue($expectedExpireAt));

        $reminderData->expects($this->once())
            ->method('getRecipient')
            ->will($this->returnValue($expectedRecipient));

        $this->entity->setReminderData($reminderData);

        $this->assertEquals($expectedSubject, $this->entity->getSubject());
        $this->assertEquals($expectedExpireAt, $this->entity->getExpireAt());
        $this->assertEquals($expectedRecipient, $this->entity->getRecipient());
    }

    public function testSetFailureException()
    {
        $expectedMessage = 'Expected message';
        $expectedCode = 100;
        $exception = new \Exception($expectedMessage, $expectedCode);

        $expected = array(
            'class' => get_class($exception),
            'message' => $expectedMessage,
            'code' => $expectedCode,
            'trace' => $exception->getTraceAsString(),
        );

        $this->entity->setFailureException($exception);

        $this->assertEquals($expected, $this->entity->getFailureException());
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
