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

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return 'test_id';
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->alertData;
    }
}
