<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Entity;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\ReminderDataInterface;
use Oro\Bundle\ReminderBundle\Model\ReminderInterval;
use Oro\Bundle\UserBundle\Entity\User;

class ReminderTest extends \PHPUnit\Framework\TestCase
{
    /** @var Reminder */
    protected $entity;

    protected function setUp(): void
    {
        $this->entity = new class() extends Reminder {
            public function xgetIntervalNumber(): int
            {
                return $this->intervalNumber;
            }

            public function xgetIntervalUnit(): string
            {
                return $this->intervalUnit;
            }
        };
    }

    public function testCreate()
    {
        static::assertEmpty($this->entity->getId());
        static::assertEquals(Reminder::STATE_NOT_SENT, $this->entity->getState());
    }

    public function testIntervalAndStartAt()
    {
        $expireAt = new \DateTime('2014-01-15');
        $number   = 3;
        $unit     = ReminderInterval::UNIT_DAY;
        $interval = new ReminderInterval($number, $unit);

        static::assertNull($this->entity->getStartAt());
        $this->entity->setExpireAt($expireAt);
        $this->entity->setInterval($interval);
        static::assertEquals($number, $this->entity->xgetIntervalNumber());
        static::assertEquals($unit, $this->entity->xgetIntervalUnit());
        static::assertEquals(new \DateTime('2014-01-12'), $this->entity->getStartAt());
    }

    public function testPrePersist()
    {
        $this->entity->prePersist();

        static::assertEquals($this->entity->getCreatedAt()->format('Y-m-d'), date('Y-m-d'));
    }

    public function testPreUpdate()
    {
        $this->entity->preUpdate();

        static::assertEquals($this->entity->getUpdatedAt()->format('Y-m-d'), date('Y-m-d'));
    }

    public function testToString()
    {
        $this->entity->setSubject('subject');

        static::assertEquals('subject', (string)$this->entity);
    }

    public function testSetSentState()
    {
        $this->entity->setState(Reminder::STATE_SENT);

        static::assertEquals(Reminder::STATE_SENT, $this->entity->getState());
        static::assertInstanceOf('DateTime', $this->entity->getSentAt());
    }

    public function testSetReminderData()
    {
        $expectedSubject   = 'subject';
        $expectedExpireAt  = new \DateTime();
        $expectedRecipient = $this->createMock(User::class);

        $reminderData = $this->createMock(ReminderDataInterface::class);

        $reminderData->expects(static::once())->method('getSubject')->willReturn($expectedSubject);
        $reminderData->expects(static::once())->method('getExpireAt')->willReturn($expectedExpireAt);
        $reminderData->expects(static::once())->method('getRecipient')->willReturn($expectedRecipient);

        $this->entity->setReminderData($reminderData);

        static::assertEquals($expectedSubject, $this->entity->getSubject());
        static::assertEquals($expectedExpireAt, $this->entity->getExpireAt());
        static::assertEquals($expectedRecipient, $this->entity->getRecipient());
    }

    public function testSetFailureException()
    {
        $expectedMessage = 'Expected message';
        $expectedCode    = 100;
        $exception       = new \Exception($expectedMessage, $expectedCode);

        $expected = [
            'class'   => get_class($exception),
            'message' => $expectedMessage,
            'code'    => $expectedCode,
            'trace'   => $exception->getTraceAsString(),
        ];

        $this->entity->setFailureException($exception);

        static::assertEquals($expected, $this->entity->getFailureException());
    }

    /**
     * @dataProvider settersAndGettersDataProvider
     */
    public function testSettersAndGetters($value, $property, $getter = null, $setter = null)
    {
        $getter = $getter ? : 'get' . \ucfirst($property);
        $setter = $setter ? : 'set' . \ucfirst($property);

        static::assertEquals($this->entity, $this->entity->$setter($value));
        static::assertEquals($value, $this->entity->$getter());
    }

    public function settersAndGettersDataProvider()
    {
        return [
            'subject'                => [
                'value'    => 'value',
                'property' => 'subject',
            ],
            'expireAt'               => [
                'value'    => new \DateTime(),
                'property' => 'expireAt',
            ],
            'method'                 => [
                'value'    => 'email',
                'property' => 'method',
            ],
            'interval'               => [
                'value'    => new ReminderInterval(1, ReminderInterval::UNIT_DAY),
                'property' => 'interval',
            ],
            'state'                  => [
                'value'    => Reminder::STATE_NOT_SENT,
                'property' => 'state',
            ],
            'relatedEntityId'        => [
                'value'    => 1,
                'property' => 'relatedEntityId',
            ],
            'relatedEntityClassName' => [
                'value'    => 'Namespace\\Entity',
                'property' => 'relatedEntityClassName',
            ],
            'recipient'              => [
                'value'    => $this->createMock(User::class),
                'property' => 'recipient',
            ],
            'createdAt'              => [
                'value'    => new \DateTime(),
                'property' => 'createdAt',
            ],
            'updatedAt'              => [
                'value'    => new \DateTime(),
                'property' => 'updatedAt',
            ],
            'sentAt'                 => [
                'value'    => new \DateTime(),
                'property' => 'sentAt',
            ],
        ];
    }
}
