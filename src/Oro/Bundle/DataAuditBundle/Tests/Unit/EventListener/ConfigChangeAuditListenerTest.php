<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\DataAuditBundle\Async\Topic\ConfigChangeAuditTopic;
use Oro\Bundle\DataAuditBundle\EventListener\ConfigChangeAuditListener;
use Oro\Bundle\DataAuditBundle\Model\ConfigAuditValueNormalizer;
use Oro\Bundle\DataAuditBundle\Provider\AuditMessageBodyProvider;
use Oro\Bundle\DataAuditBundle\Provider\ConfigAuditLevelProvider;
use Oro\Bundle\DataAuditBundle\Provider\SensitiveConfigFieldProvider;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ConfigChangeAuditListenerTest extends TestCase
{
    private TokenStorageInterface&MockObject $tokenStorage;
    private FeatureChecker&MockObject $featureChecker;
    private MessageProducerInterface&MockObject $messageProducer;
    private ConfigBag&MockObject $configBag;
    private bool $installed = true;
    private ?string $sentTopic = null;
    private ConfigChangeAuditListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->configBag = $this->createMock(ConfigBag::class);

        $applicationState = $this->createMock(ApplicationState::class);
        $applicationState->expects(self::any())
            ->method('isInstalled')
            ->willReturnCallback(fn (): bool => $this->installed);

        $entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->listener = new ConfigChangeAuditListener(
            $this->createMock(ManagerRegistry::class),
            $this->tokenStorage,
            $entityNameResolver,
            $this->featureChecker,
            $this->messageProducer,
            $applicationState,
            new ConfigAuditValueNormalizer($this->configBag, $this->createMock(SensitiveConfigFieldProvider::class)),
            new AuditMessageBodyProvider($entityNameResolver),
            new ConfigAuditLevelProvider([
                'customer' => 'Oro\\Bundle\\CustomerBundle\\Entity\\Customer',
                'customer_group' => 'Oro\\Bundle\\CustomerBundle\\Entity\\CustomerGroup',
                'website' => 'Oro\\Bundle\\WebsiteBundle\\Entity\\Website',
                'user' => 'Oro\\Bundle\\UserBundle\\Entity\\User',
                'organization' => 'Oro\\Bundle\\OrganizationBundle\\Entity\\Organization',
                'global' => null,
            ])
        );
    }

    public function testDoesNothingWhenApplicationNotInstalled(): void
    {
        $this->installed = false;
        $this->featureChecker->expects(self::never())
            ->method('isFeatureEnabled');
        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->listener->onConfigUpdate(
            new ConfigUpdateEvent(['oro_test.foo' => ['old' => 'a', 'new' => 'b', 'action' => 'update']], 'global', 0)
        );
    }

    public function testDoesNothingWhenFeatureDisabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('data_audit')
            ->willReturn(false);
        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->listener->onConfigUpdate(
            new ConfigUpdateEvent(['oro_test.foo' => ['old' => 'a', 'new' => 'b', 'action' => 'update']], 'global', 0)
        );
    }

    public function testDoesNothingWithoutSecurityToken(): void
    {
        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);
        $this->messageProducer->expects(self::never())
            ->method('send');
        $this->listener->onConfigUpdate(
            new ConfigUpdateEvent(['oro_test.foo' => ['old' => 'a', 'new' => 'b', 'action' => 'update']], 'global', 0)
        );
    }

    public function testDoesNothingWhenChangeSetIsEmpty(): void
    {
        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->listener->onConfigUpdate(new ConfigUpdateEvent([], 'global', 0));
    }

    public function testPublishesConfigChangeStoringTheKey(): void
    {
        $this->configureListenerForField(['options' => ['label' => 'label.foo']]);

        $message = $this->captureSentMessage(
            ['oro_test.foo' => ['old' => 'a', 'new' => 'b', 'action' => 'update']]
        );

        self::assertSame(ConfigChangeAuditTopic::getName(), $this->sentTopic);
        self::assertSame('Oro\Bundle\ConfigBundle\SystemConfiguration', $message['object_class']);
        self::assertSame('0', $message['object_id']);
        self::assertSame('Global', $message['object_name']);
        self::assertSame('update', $message['action']);
        self::assertSame(
            ['field' => 'oro_test.foo', 'type' => 'text', 'old' => 'a', 'new' => 'b'],
            $message['changes']['oro_test.foo']
        );
        self::assertArrayHasKey('transaction_id', $message);
        self::assertArrayNotHasKey('user_id', $message);
    }

    public function testReducesMixedActionsAndFormatsValues(): void
    {
        $this->configureListenerForField(['options' => ['label' => 'label']]);

        $message = $this->captureSentMessage([
            'oro_test.multi' => ['old' => ['x', 'y'], 'new' => [], 'action' => 'remove'],
            'oro_test.foo' => ['old' => null, 'new' => 'b', 'action' => 'create'],
        ]);

        self::assertSame('update', $message['action']);
        self::assertSame(
            ['field' => 'oro_test.multi', 'type' => 'text', 'old' => 'x, y', 'new' => null],
            $message['changes']['oro_test.multi']
        );
        self::assertSame(
            ['field' => 'oro_test.foo', 'type' => 'text', 'old' => null, 'new' => 'b'],
            $message['changes']['oro_test.foo']
        );
    }

    public function testBlanksPreviousValueOnCreate(): void
    {
        $this->configureListenerForField(['options' => ['label' => 'Some setting']]);

        $message = $this->captureSentMessage(
            ['oro_test.foo' => ['old' => 'inherited default', 'new' => 'explicit value', 'action' => 'create']]
        );

        self::assertNull($message['changes']['oro_test.foo']['old']);
        self::assertSame('explicit value', $message['changes']['oro_test.foo']['new']);
    }

    public function testBlanksNewValueOnRemove(): void
    {
        $this->configureListenerForField(['options' => ['label' => 'Some setting']]);

        $message = $this->captureSentMessage(
            ['oro_test.foo' => ['old' => 'explicit value', 'new' => 'fallback default', 'action' => 'remove']]
        );

        self::assertSame('explicit value', $message['changes']['oro_test.foo']['old']);
        self::assertNull($message['changes']['oro_test.foo']['new']);
    }

    public function testKeepsTheConfigurationDataType(): void
    {
        $this->configureListenerForField(['data_type' => 'boolean', 'options' => ['label' => 'Enabled']]);

        $message = $this->captureSentMessage(
            ['oro_test.enabled' => ['old' => '1', 'new' => '0', 'action' => 'update']]
        );

        self::assertSame(
            ['field' => 'oro_test.enabled', 'type' => 'boolean', 'old' => true, 'new' => false],
            $message['changes']['oro_test.enabled']
        );
    }

    public function testPublishesOwnValueTakenOutOfTheParentScope(): void
    {
        $this->configureListenerForField(['options' => ['label' => 'Language']]);

        $message = $this->captureSentMessage(
            [],
            ['oro_locale.language' => ['old' => 'en', 'new' => 'en', 'action' => 'create']]
        );

        self::assertSame('create', $message['action']);
        self::assertSame(
            ['field' => 'oro_locale.language', 'type' => 'text', 'old' => null, 'new' => 'en'],
            $message['changes']['oro_locale.language']
        );
    }

    public function testPublishesValueGivenBackToTheParentScope(): void
    {
        $this->configureListenerForField(['options' => ['label' => 'Language']]);

        $message = $this->captureSentMessage(
            [],
            ['oro_locale.language' => ['old' => 'en', 'new' => 'en', 'action' => 'remove']]
        );

        self::assertSame('remove', $message['action']);
        self::assertSame(
            ['field' => 'oro_locale.language', 'type' => 'text', 'old' => 'en', 'new' => null],
            $message['changes']['oro_locale.language']
        );
    }

    public function testPublishesValueAndUseParentScopeChangesOfOneSaveTogether(): void
    {
        $this->configureListenerForField(['options' => ['label' => 'label']]);

        $message = $this->captureSentMessage(
            ['oro_test.foo' => ['old' => 'a', 'new' => 'b', 'action' => 'update']],
            ['oro_test.bar' => ['old' => 'c', 'new' => 'c', 'action' => 'create']]
        );

        self::assertSame('update', $message['action']);
        self::assertSame(['oro_test.foo', 'oro_test.bar'], array_keys($message['changes']));
    }

    private function configureListenerForField(array $fieldDefinition): void
    {
        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->tokenStorage->expects(self::any())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->configBag->expects(self::any())
            ->method('getFieldsRoot')
            ->willReturn($fieldDefinition);
    }

    private function captureSentMessage(array $changeSet, array $useParentScopeChanges = []): array
    {
        $captured = [];
        $this->messageProducer->expects(self::once())
            ->method('send')
            ->willReturnCallback(function (string $topic, $message) use (&$captured): void {
                $this->sentTopic = $topic;
                $captured = $message;
            });

        $this->listener->onConfigUpdate(
            new ConfigUpdateEvent($changeSet, 'global', 0, $useParentScopeChanges)
        );

        return $captured;
    }
}
