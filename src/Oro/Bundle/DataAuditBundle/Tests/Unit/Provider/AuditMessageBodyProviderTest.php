<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Provider;

use Oro\Bundle\DataAuditBundle\Provider\AuditMessageBodyProvider;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\AbstractUserStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuditMessageBodyProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
    }

    public function testPrepareMessageBodyEmptyParameters()
    {
        $provider = new AuditMessageBodyProvider($this->entityNameResolver);

        $securityToken = $this->createMock(TokenInterface::class);
        $securityToken->expects(self::never())
            ->method('getUser')
            ->willReturn(null);
        $securityToken->expects(self::never())
            ->method('hasAttribute');

        $body = $provider->prepareMessageBody(
            [],
            [],
            [],
            [],
            $securityToken
        );

        self::assertEmpty($body);
    }

    public function testPrepareMessageBodyEmptyUserNoImpersonationAttributeNoOwnerAttribute()
    {
        $provider = new AuditMessageBodyProvider($this->entityNameResolver);

        $securityToken = $this->createMock(TokenInterface::class);
        $securityToken->expects(self::once())
            ->method('getUser')
            ->willReturn(null);
        $securityToken->expects(self::exactly(2))
            ->method('hasAttribute')
            ->withConsecutive(['IMPERSONATION'], ['owner_description'])
            ->willReturnOnConsecutiveCalls(false, false);

        $this->entityNameResolver->expects(self::never())
            ->method('getName');

        $body = $provider->prepareMessageBody(
            ['insertions'],
            [],
            [],
            [],
            $securityToken
        );

        self::assertEquals(['insertions'], $body['entities_inserted']);
        self::assertEquals([], $body['entities_updated']);
        self::assertEquals([], $body['entities_deleted']);
        self::assertEquals([], $body['collections_updated']);

        self::assertNotEmpty($body['timestamp']);
        self::assertNotEmpty($body['transaction_id']);

        self::assertArrayNotHasKey('user_id', $body);
        self::assertArrayNotHasKey('user_class', $body);

        self::assertArrayNotHasKey('organization_id', $body);
        self::assertArrayNotHasKey('impersonation_id', $body);
        self::assertArrayNotHasKey('owner_description', $body);
    }

    public function testPrepareMessageBodyUserInterfaceNoImpersonationAttributeNoOwnerAttribute()
    {
        $provider = new AuditMessageBodyProvider($this->entityNameResolver);

        $user = $this->createMock(UserInterface::class);

        $securityToken = $this->createMock(TokenInterface::class);
        $securityToken->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $securityToken->expects(self::exactly(2))
            ->method('hasAttribute')
            ->withConsecutive(['IMPERSONATION'], ['owner_description'])
            ->willReturnOnConsecutiveCalls(false, false);

        $body = $provider->prepareMessageBody(
            [],
            ['updates'],
            [],
            [],
            $securityToken
        );

        self::assertEquals([], $body['entities_inserted']);
        self::assertEquals(['updates'], $body['entities_updated']);
        self::assertEquals([], $body['entities_deleted']);
        self::assertEquals([], $body['collections_updated']);

        self::assertNotEmpty($body['timestamp']);
        self::assertNotEmpty($body['transaction_id']);

        self::assertArrayNotHasKey('user_id', $body);
        self::assertArrayNotHasKey('user_class', $body);

        self::assertArrayNotHasKey('organization_id', $body);
        self::assertArrayNotHasKey('impersonation_id', $body);
        self::assertArrayNotHasKey('owner_description', $body);
    }

    public function testPrepareMessageBodyAbstractUserNoImpersonationAttributeNoOwnerAttribute()
    {
        $provider = new AuditMessageBodyProvider($this->entityNameResolver);

        $user = $this->getEntity(AbstractUserStub::class, ['id' => 23]);

        $securityToken = $this->createMock(TokenInterface::class);
        $securityToken->expects(self::atLeastOnce())
            ->method('getUser')
            ->willReturn($user);
        $securityToken->expects(self::any())
            ->method('hasAttribute')
            ->willReturn(false);

        $this->entityNameResolver->expects(self::atLeastOnce())
            ->method('getName')
            ->with($user, 'email')
            ->willReturn('user@name.com');

        $body = $provider->prepareMessageBody(
            [],
            [],
            ['deletions'],
            [],
            $securityToken
        );

        self::assertEquals([], $body['entities_inserted']);
        self::assertEquals([], $body['entities_updated']);
        self::assertEquals(['deletions'], $body['entities_deleted']);
        self::assertEquals([], $body['collections_updated']);

        self::assertNotEmpty($body['timestamp']);
        self::assertNotEmpty($body['transaction_id']);

        self::assertArrayHasKey('user_id', $body);
        self::assertEquals(23, $body['user_id']);
        self::assertArrayHasKey('user_class', $body);
        self::assertEquals(AbstractUserStub::class, $body['user_class']);

        self::assertArrayNotHasKey('organization_id', $body);
        self::assertArrayNotHasKey('impersonation_id', $body);
        self::assertArrayHasKey('owner_description', $body);
        self::assertEquals('user@name.com', $body['owner_description']);

        // assert same transaction id on second call
        $transactionId = $body['transaction_id'];

        $body = $provider->prepareMessageBody(
            [],
            [],
            ['deletions'],
            [],
            $securityToken
        );

        self::assertNotEmpty($body['transaction_id']);
        self::assertEquals($transactionId, $body['transaction_id']);
    }

    public function testPrepareMessageBodyEmptyUserNoImpersonationAttributeNoOwnerAttributeOrganizationToken()
    {
        $provider = new AuditMessageBodyProvider($this->entityNameResolver);

        $securityToken = $this->createMock(OrganizationAwareTokenInterface::class);

        $securityToken->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        $this->entityNameResolver->expects(self::never())
            ->method('getName');

        $organization = $this->getEntity(Organization::class, ['id' => 75]);
        $securityToken->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $securityToken->expects(self::exactly(2))
            ->method('hasAttribute')
            ->withConsecutive(['IMPERSONATION'], ['owner_description'])
            ->willReturnOnConsecutiveCalls(false, false);

        $body = $provider->prepareMessageBody(
            [],
            [],
            [],
            ['collectionUpdates'],
            $securityToken
        );

        self::assertEquals([], $body['entities_inserted']);
        self::assertEquals([], $body['entities_updated']);
        self::assertEquals([], $body['entities_deleted']);
        self::assertEquals(['collectionUpdates'], $body['collections_updated']);

        self::assertNotEmpty($body['timestamp']);
        self::assertNotEmpty($body['transaction_id']);

        self::assertArrayNotHasKey('user_id', $body);
        self::assertArrayNotHasKey('user_class', $body);

        self::assertArrayHasKey('organization_id', $body);
        self::assertEquals(75, $body['organization_id']);

        self::assertArrayNotHasKey('impersonation_id', $body);
        self::assertArrayNotHasKey('owner_description', $body);
    }

    public function testPrepareMessageBodyEmptyUserHasImpersonationAttributeNoOwnerAttribute()
    {
        $provider = new AuditMessageBodyProvider($this->entityNameResolver);

        $securityToken = $this->createMock(TokenInterface::class);
        $securityToken->expects(self::once())
            ->method('getUser')
            ->willReturn(null);
        $securityToken->expects(self::exactly(2))
            ->method('hasAttribute')
            ->withConsecutive(['IMPERSONATION'], ['owner_description'])
            ->willReturnOnConsecutiveCalls(true, false);
        $securityToken->expects(self::once())
            ->method('getAttribute')
            ->with('IMPERSONATION')
            ->willReturn('impersonation_attribute');

        $this->entityNameResolver->expects(self::never())
            ->method('getName');

        $body = $provider->prepareMessageBody(
            ['insertions'],
            ['updates'],
            ['deletions'],
            ['collectionUpdates'],
            $securityToken
        );

        self::assertEquals(['insertions'], $body['entities_inserted']);
        self::assertEquals(['updates'], $body['entities_updated']);
        self::assertEquals(['deletions'], $body['entities_deleted']);
        self::assertEquals(['collectionUpdates'], $body['collections_updated']);

        self::assertNotEmpty($body['timestamp']);
        self::assertNotEmpty($body['transaction_id']);

        self::assertArrayNotHasKey('user_id', $body);
        self::assertArrayNotHasKey('user_class', $body);

        self::assertArrayNotHasKey('organization_id', $body);

        self::assertArrayHasKey('impersonation_id', $body);
        self::assertEquals('impersonation_attribute', $body['impersonation_id']);

        self::assertArrayNotHasKey('owner_description', $body);
    }

    public function testPrepareMessageBodyEmptyUserNoImpersonationAttributeHasOwnerAttribute()
    {
        $provider = new AuditMessageBodyProvider($this->entityNameResolver);

        $securityToken = $this->createMock(TokenInterface::class);
        $securityToken->expects(self::once())
            ->method('getUser')
            ->willReturn(null);
        $securityToken->expects(self::exactly(2))
            ->method('hasAttribute')
            ->withConsecutive(['IMPERSONATION'], ['owner_description'])
            ->willReturnOnConsecutiveCalls(false, true);
        $securityToken->expects(self::once())
            ->method('getAttribute')
            ->with('owner_description')
            ->willReturn('owner_description_attribute');

        $this->entityNameResolver->expects(self::never())
            ->method('getName');

        $body = $provider->prepareMessageBody(
            ['insertions'],
            ['updates'],
            ['deletions'],
            ['collectionUpdates'],
            $securityToken
        );

        self::assertEquals(['insertions'], $body['entities_inserted']);
        self::assertEquals(['updates'], $body['entities_updated']);
        self::assertEquals(['deletions'], $body['entities_deleted']);
        self::assertEquals(['collectionUpdates'], $body['collections_updated']);

        self::assertNotEmpty($body['timestamp']);
        self::assertNotEmpty($body['transaction_id']);

        self::assertArrayNotHasKey('user_id', $body);
        self::assertArrayNotHasKey('user_class', $body);

        self::assertArrayNotHasKey('organization_id', $body);
        self::assertArrayNotHasKey('impersonation_id', $body);

        self::assertArrayHasKey('owner_description', $body);
        self::assertEquals('owner_description_attribute', $body['owner_description']);
    }

    public function testPrepareMessageBodyEmptyToken()
    {
        $provider = new AuditMessageBodyProvider($this->entityNameResolver);

        $this->entityNameResolver->expects(self::never())
            ->method('getName');

        $body = $provider->prepareMessageBody(
            ['insertions'],
            ['updates'],
            ['deletions'],
            ['collectionUpdates'],
            null
        );

        self::assertEquals(['insertions'], $body['entities_inserted']);
        self::assertEquals(['updates'], $body['entities_updated']);
        self::assertEquals(['deletions'], $body['entities_deleted']);
        self::assertEquals(['collectionUpdates'], $body['collections_updated']);

        self::assertNotEmpty($body['timestamp']);
        self::assertNotEmpty($body['transaction_id']);

        self::assertArrayNotHasKey('user_id', $body);
        self::assertArrayNotHasKey('user_class', $body);
        self::assertArrayNotHasKey('organization_id', $body);
        self::assertArrayNotHasKey('impersonation_id', $body);
        self::assertArrayNotHasKey('owner_description', $body);
    }
}
