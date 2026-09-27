<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Service;

use Oro\Bundle\DataAuditBundle\Async\Topic\AuditEntryTopic;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Model\AuditEntry;
use Oro\Bundle\DataAuditBundle\Provider\AuditMessageBodyProvider;
use Oro\Bundle\DataAuditBundle\Service\AuditEntryRecorder;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuditEntryRecorderTest extends TestCase
{
    private MessageProducerInterface&MockObject $messageProducer;
    private TokenStorageInterface&MockObject $tokenStorage;
    private FeatureChecker&MockObject $featureChecker;
    private bool $installed = true;
    private ?string $sentTopic = null;
    private AuditEntryRecorder $recorder;

    #[\Override]
    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $applicationState = $this->createMock(ApplicationState::class);
        $applicationState->expects(self::any())
            ->method('isInstalled')
            ->willReturnCallback(fn (): bool => $this->installed);

        $this->recorder = new AuditEntryRecorder(
            $this->messageProducer,
            $this->tokenStorage,
            new AuditMessageBodyProvider($this->createMock(EntityNameResolver::class)),
            $this->featureChecker,
            $applicationState
        );
    }

    public function testRecordsTheEntryWithItsChanges(): void
    {
        $this->givenAuditEnabled();

        $captured = [];
        $this->messageProducer->expects(self::once())
            ->method('send')
            ->willReturnCallback(function (string $topic, $message) use (&$captured): void {
                $this->sentTopic = $topic;
                $captured = $message;
            });

        $entry = new AuditEntry('Some\Virtual\Type', '42', 'Main Menu', Audit::ACTION_UPDATE);
        $entry->addChange('Contact Us', 'Contact Us', 'Contact');

        $this->recorder->record($entry);

        self::assertSame(AuditEntryTopic::getName(), $this->sentTopic);
        self::assertSame('Some\Virtual\Type', $captured['object_class']);
        self::assertSame('42', $captured['object_id']);
        self::assertSame('Main Menu', $captured['object_name']);
        self::assertSame(Audit::ACTION_UPDATE, $captured['action']);
        self::assertSame(
            ['field' => 'Contact Us', 'type' => 'text', 'old' => 'Contact Us', 'new' => 'Contact'],
            $captured['changes']['Contact Us']
        );
        self::assertArrayHasKey('timestamp', $captured);
        self::assertArrayHasKey('transaction_id', $captured);
    }

    public function testDoesNotRecordAnEntryWithoutChanges(): void
    {
        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->recorder->record(new AuditEntry('Some\Virtual\Type', '42', 'Main Menu', Audit::ACTION_UPDATE));
    }

    public function testDoesNotRecordWhenApplicationNotInstalled(): void
    {
        $this->installed = false;
        $this->featureChecker->expects(self::never())
            ->method('isFeatureEnabled');
        $this->messageProducer->expects(self::never())
            ->method('send');

        self::assertFalse($this->recorder->isEnabled());
        $this->recorder->record($this->createEntryWithChange());
    }

    public function testDoesNotRecordWhenFeatureDisabled(): void
    {
        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->with('data_audit')
            ->willReturn(false);
        $this->messageProducer->expects(self::never())
            ->method('send');

        self::assertFalse($this->recorder->isEnabled());
        $this->recorder->record($this->createEntryWithChange());
    }

    public function testDoesNotRecordWithoutSecurityToken(): void
    {
        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->tokenStorage->expects(self::any())
            ->method('getToken')
            ->willReturn(null);
        $this->messageProducer->expects(self::never())
            ->method('send');

        self::assertFalse($this->recorder->isEnabled());
        $this->recorder->record($this->createEntryWithChange());
    }

    public function testIsEnabledWhenInstalledEnabledAndAuthored(): void
    {
        $this->givenAuditEnabled();

        self::assertTrue($this->recorder->isEnabled());
    }

    private function givenAuditEnabled(): void
    {
        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->tokenStorage->expects(self::any())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
    }

    private function createEntryWithChange(): AuditEntry
    {
        return (new AuditEntry('Some\Virtual\Type', '42', 'Main Menu', Audit::ACTION_UPDATE))
            ->addChange('Contact Us', 'Contact Us', 'Contact');
    }
}
