<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Fixtures\NotificationAlert;

use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertInterface;

class TestNotificationAlert implements NotificationAlertInterface
{
    private string $sourceType;
    private array $alertData;

    public function __construct(string $sourceType, array $alertData)
    {
        $this->sourceType = $sourceType;
        $this->alertData = $alertData;
    }

    #[\Override]
    public function getId(): string
    {
        return 'test_id';
    }

    #[\Override]
    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    #[\Override]
    public function toArray(): array
    {
        return $this->alertData;
    }
}
