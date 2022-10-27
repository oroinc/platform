<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Entity;

use Oro\Bundle\NotificationBundle\Entity\MassNotification;

class MassNotificationTest extends \PHPUnit\Framework\TestCase
{
    private MassNotification $massNotification;

    protected function setUp(): void
    {
        $this->massNotification = new MassNotification();

        // get id should return null cause this entity was not loaded from DB
        self::assertNull($this->massNotification->getId());
    }

    /**
     * @dataProvider getSetDataProvider
     */
    public function testGetSet(string $property, mixed $value, $expected): void
    {
        call_user_func([$this->massNotification, 'set' . ucfirst($property)], $value);
        self::assertEquals(
            $expected,
            call_user_func_array([$this->massNotification, 'get' . ucfirst($property)], [])
        );
    }

    public function getSetDataProvider(): array
    {
        $date = new \DateTime('now');
        return [
            'email' => ['email', 'test@test.com', 'test@test.com'],
            'sender' => ['sender', 'from@test.com', 'from@test.com'],
            'body' => ['body', 'test body', 'test body'],
            'subject' => ['subject', 'test title', 'test title'],
            'scheduledAt' => ['scheduledAt', $date, $date],
            'processedAt' => ['processedAt', $date, $date],
            'status' => ['status', 1, 1],
        ];
    }
}
