<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\EmailBundle\EventListener\Doctrine\UpdateMessageQueueCacheStateDoctrineListener;
use Oro\Bundle\MessageQueueBundle\Consumption\CacheState;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateMessageQueueCacheStateDoctrineListenerTest extends TestCase
{
    private CacheState&MockObject $cacheState;
    private UnitOfWork&MockObject $unitOfWork;
    private OnFlushEventArgs&MockObject $event;
    private UpdateMessageQueueCacheStateDoctrineListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->cacheState = $this->createMock(CacheState::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->event = $this->createMock(OnFlushEventArgs::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);
        $this->event->expects(self::once())
            ->method('getObjectManager')
            ->willReturn($entityManager);

        $this->listener = new UpdateMessageQueueCacheStateDoctrineListener($this->cacheState);
    }

    /**
     * @dataProvider noScheduledEmailTemplatesDataProvider
     */
    public function testWhenNoScheduledEmailTemplates(array $insertions, array $updates, $deletions): void
    {
        $this->unitOfWork->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn($insertions);
        $this->unitOfWork->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn($updates);
        $this->unitOfWork->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn($deletions);

        $this->cacheState->expects(self::never())
            ->method(self::anything());

        $this->listener->onFlush($this->event);
        $this->listener->postFlush();
    }

    public function noScheduledEmailTemplatesDataProvider(): array
    {
        return [
            'no scheduled changes' => [
                'insertions' => [],
                'updates' => [],
                'deletions' => []
            ],
            'no scheduled email templates' => [
                'insertions' => [new \stdClass()],
                'updates' => [new \stdClass()],
                'deletions' => [new \stdClass()]
            ]
        ];
    }

    /**
     * @dataProvider hasScheduledEmailTemplatesDataProvider
     */
    public function testWhenHasScheduledEmailTemplates(array $insertions, array $updates, $deletions): void
    {
        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityInsertions')
            ->willReturn($insertions);
        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityUpdates')
            ->willReturn($updates);
        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityDeletions')
            ->willReturn($deletions);

        $this->cacheState->expects(self::once())
            ->method('renewChangeDate');

        $this->listener->onFlush($this->event);
        $this->listener->postFlush();

        // Ensures that the cache is updated only once.
        $this->listener->postFlush();
    }

    public function hasScheduledEmailTemplatesDataProvider(): array
    {
        return [
            'has scheduled email template insertion' => [
                'insertions' => [new EmailTemplateEntity()],
                'updates' => [new \stdClass()],
                'deletions' => [new \stdClass()]
            ],
            'has scheduled email template update' => [
                'insertions' => [new \stdClass()],
                'updates' => [new EmailTemplateEntity()],
                'deletions' => [new \stdClass()]
            ],
                'has scheduled email template deletion' => [
                'insertions' => [new \stdClass()],
                'updates' => [new \stdClass()],
                'deletions' => [new EmailTemplateEntity()]
            ]
        ];
    }
}
