<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DataAuditBundle\Async\ConfigChangeAuditProcessor;
use Oro\Bundle\DataAuditBundle\Async\Topic\ConfigChangeAuditTopic;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Service\SetNewAuditVersionService;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigChangeAuditProcessorTest extends TestCase
{
    private ObjectManager&MockObject $em;
    private SetNewAuditVersionService&MockObject $setNewAuditVersionService;
    private ConfigChangeAuditProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->createMock(ObjectManager::class);
        $this->setNewAuditVersionService = $this->createMock(SetNewAuditVersionService::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->processor = new ConfigChangeAuditProcessor($doctrine, $this->setNewAuditVersionService);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertSame(
            [ConfigChangeAuditTopic::getName()],
            ConfigChangeAuditProcessor::getSubscribedTopics()
        );
    }

    public function testProcessBuildsAndPersistsAudit(): void
    {
        $data = [
            'timestamp' => 1_700_000_000,
            'transaction_id' => 'tx-1',
            'object_class' => 'Oro\Bundle\ConfigBundle\SystemConfiguration',
            'object_id' => '0',
            'object_name' => 'Global',
            'action' => Audit::ACTION_UPDATE,
            'changes' => [
                'oro_test.foo' => ['field' => 'General › Foo', 'type' => 'text', 'old' => 'a', 'new' => 'b'],
                'oro_test.enabled' => ['field' => 'Enabled', 'type' => 'boolean', 'old' => true, 'new' => false],
            ],
        ];

        $persisted = null;
        $this->em->expects(self::once())
            ->method('persist')
            ->willReturnCallback(function (object $audit) use (&$persisted): void {
                $persisted = $audit;
            });
        $this->em->expects(self::once())
            ->method('flush');
        $this->setNewAuditVersionService->expects(self::once())
            ->method('setVersion')
            ->with(self::isInstanceOf(Audit::class));

        $result = $this->processor->process(
            $this->createMessage($data),
            $this->createMock(SessionInterface::class)
        );

        self::assertSame(MessageProcessorInterface::ACK, $result);
        self::assertInstanceOf(Audit::class, $persisted);
        self::assertSame('Oro\Bundle\ConfigBundle\SystemConfiguration', $persisted->getObjectClass());
        self::assertSame('0', $persisted->getObjectId());
        self::assertSame('Global', $persisted->getObjectName());
        self::assertSame(Audit::ACTION_UPDATE, $persisted->getAction());
        self::assertSame('tx-1', $persisted->getTransactionId());
        self::assertCount(2, $persisted->getFields());

        $fieldsByName = [];
        foreach ($persisted->getFields() as $field) {
            $fieldsByName[$field->getField()] = $field;
        }
        // Each audit field keeps the configuration data type: text as text, boolean as a real boolean.
        self::assertSame('text', $fieldsByName['General › Foo']->getDataType());
        self::assertSame('boolean', $fieldsByName['Enabled']->getDataType());
        self::assertTrue($fieldsByName['Enabled']->getOldValue());
        self::assertFalse($fieldsByName['Enabled']->getNewValue());
    }

    public function testProcessResolvesAuthorFromTheMessage(): void
    {
        $user = new User();
        $organization = new Organization();
        $impersonation = new Impersonation();
        $this->em->expects(self::any())
            ->method('find')
            ->willReturnCallback(static fn (string $class): ?object => match ($class) {
                User::class => $user,
                Organization::class => $organization,
                Impersonation::class => $impersonation,
                default => null,
            });

        $persisted = $this->process([
            'user_id' => 1,
            'user_class' => User::class,
            'organization_id' => 2,
            'impersonation_id' => 3,
            'owner_description' => 'John Doe - admin@example.com',
        ]);

        self::assertSame($user, $persisted->getUser());
        self::assertSame($organization, $persisted->getOrganization());
        self::assertSame($impersonation, $persisted->getImpersonation());
        self::assertSame('John Doe - admin@example.com', $persisted->getOwnerDescription());
    }

    public function testProcessIgnoresAuthorThatIsNotABackOfficeUser(): void
    {
        // A storefront customer user cannot be stored in the audit's user association.
        $this->em->expects(self::any())
            ->method('find')
            ->willReturn(new \stdClass());

        $persisted = $this->process([
            'user_id' => 5,
            'user_class' => 'Oro\Bundle\CustomerBundle\Entity\CustomerUser',
            'owner_description' => 'Amanda Cole',
        ]);

        self::assertNull($persisted->getUser());
        self::assertSame('Amanda Cole', $persisted->getOwnerDescription());
    }

    public function testProcessSkipsWhenNoChanges(): void
    {
        $this->em->expects(self::never())
            ->method('persist');
        $this->em->expects(self::never())
            ->method('flush');
        $this->setNewAuditVersionService->expects(self::never())
            ->method('setVersion');

        $result = $this->processor->process(
            $this->createMessage(['changes' => []]),
            $this->createMock(SessionInterface::class)
        );

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }

    /**
     * Processes a minimal configuration change message merged with the given author data and returns the
     * persisted audit.
     */
    private function process(array $authorData): Audit
    {
        $persisted = null;
        $this->em->expects(self::once())
            ->method('persist')
            ->willReturnCallback(function (object $audit) use (&$persisted): void {
                $persisted = $audit;
            });

        $this->processor->process(
            $this->createMessage(array_merge(
                [
                    'timestamp' => 1_700_000_000,
                    'transaction_id' => 'tx-1',
                    'object_class' => 'Oro\Bundle\ConfigBundle\SystemConfiguration',
                    'object_id' => '0',
                    'object_name' => 'Global',
                    'action' => Audit::ACTION_UPDATE,
                    'changes' => [
                        'oro_test.foo' => ['field' => 'oro_test.foo', 'type' => 'text', 'old' => 'a', 'new' => 'b'],
                    ],
                ],
                $authorData
            )),
            $this->createMock(SessionInterface::class)
        );

        self::assertInstanceOf(Audit::class, $persisted);

        return $persisted;
    }

    private function createMessage(array $body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn($body);

        return $message;
    }
}
