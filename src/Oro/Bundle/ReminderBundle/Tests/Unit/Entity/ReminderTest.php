<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Entity;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\ReminderDataInterface;
use Oro\Bundle\ReminderBundle\Model\ReminderInterval;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class ReminderTest extends TestCase
{
    private Reminder $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->entity = new Reminder();
    }

    public function testCreate(): void
    {
        self::assertEmpty($this->entity->getId());
        self::assertEquals(Reminder::STATE_NOT_SENT, $this->entity->getState());
    }

    public function testIntervalAndStartAt(): void
    {
        $expireAt = new \DateTime('2014-01-15');
        $number = 3;
        $unit = ReminderInterval::UNIT_DAY;
        $interval = new ReminderInterval($number, $unit);

        self::assertNull($this->entity->getStartAt());
        $this->entity->setExpireAt($expireAt);
        $this->entity->setInterval($interval);
        self::assertEquals($number, ReflectionUtil::getPropertyValue($this->entity, 'intervalNumber'));
        self::assertEquals($unit, ReflectionUtil::getPropertyValue($this->entity, 'intervalUnit'));
        self::assertEquals(new \DateTime('2014-01-12'), $this->entity->getStartAt());
    }

    public function testPrePersist(): void
    {
        $this->entity->prePersist();

        self::assertEquals($this->entity->getCreatedAt()->format('Y-m-d'), date('Y-m-d'));
    }

    public function testPreUpdate(): void
    {
        $this->entity->preUpdate();

        self::assertEquals($this->entity->getUpdatedAt()->format('Y-m-d'), date('Y-m-d'));
    }

    public function testToString(): void
    {
        $this->entity->setSubject('subject');

        self::assertEquals('subject', (string)$this->entity);
    }

    public function testSetSentState(): void
    {
        $this->entity->setState(Reminder::STATE_SENT);

        self::assertEquals(Reminder::STATE_SENT, $this->entity->getState());
        self::assertInstanceOf('DateTime', $this->entity->getSentAt());
    }

    public function testSetReminderData(): void
    {
        $expectedSubject = 'subject';
        $expectedExpireAt = new \DateTime();
        $expectedRecipient = $this->createMock(User::class);

        $reminderData = $this->createMock(ReminderDataInterface::class);

        $reminderData->expects(self::once())
            ->method('getSubject')
            ->willReturn($expectedSubject);
        $reminderData->expects(self::once())
            ->method('getExpireAt')
            ->willReturn($expectedExpireAt);
        $reminderData->expects(self::once())
            ->method('getRecipient')
            ->willReturn($expectedRecipient);

        $this->entity->setReminderData($reminderData);

        self::assertEquals($expectedSubject, $this->entity->getSubject());
        self::assertEquals($expectedExpireAt, $this->entity->getExpireAt());
        self::assertEquals($expectedRecipient, $this->entity->getRecipient());
    }

    public function testSetFailureException(): void
    {
        $expectedMessage = 'Expected message';
        $expectedCode = 100;
        $exception = new \Exception($expectedMessage, $expectedCode);

        $expected = [
            'class'   => get_class($exception),
            'message' => $expectedMessage,
            'code'    => $expectedCode,
            'trace'   => $exception->getTraceAsString(),
        ];

        $this->entity->setFailureException($exception);

        self::assertEquals($expected, $this->entity->getFailureException());
    }

    /**
     * @dataProvider settersAndGettersDataProvider
     */
    public function testSettersAndGetters(
        mixed $value,
        string $property,
        ?string $getter = null,
        ?string $setter = null
    ): void {
        $getter = $getter ?: 'get' . \ucfirst($property);
        $setter = $setter ?: 'set' . \ucfirst($property);

        self::assertEquals($this->entity, $this->entity->$setter($value));
        self::assertEquals($value, $this->entity->$getter());
    }

    public function settersAndGettersDataProvider(): array
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
