<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\ORM\Walker;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleExecutor;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Cache\DoctrineAclCacheProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalker;
use Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalkerContext;
use Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalkerContextFactory;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AclHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclHelper */
    private $helper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $accessRuleExecutor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AdapterInterface  */
    private $cache;

    #[\Override]
    protected function setUp(): void
    {
        $this->cache = $this->createMock(AdapterInterface::class);
        $queryCacheProvider = $this->createMock(DoctrineAclCacheProvider::class);
        $queryCacheProvider->expects(self::once())
            ->method('getCurrentUserCache')
            ->willReturn($this->cache);

        $this->em = $this->createMock(EntityManagerInterface::class);
        $configuration = $this->createMock(Configuration::class);
        $this->em->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration);
        $configuration->expects($this->any())
            ->method('getDefaultQueryHints')
            ->willReturn([]);

        $this->tokenStorage = new TokenStorage();
        $this->accessRuleExecutor = $this->createMock(AccessRuleExecutor::class);

        $this->helper = new AclHelper(
            new AccessRuleWalkerContextFactory($this->tokenStorage, $this->accessRuleExecutor),
            $queryCacheProvider
        );
    }

    public function testApplyToQueryWithDefaultConfiguration()
    {
        $query = new Query($this->em);

        $this->helper->apply($query);
        $hints = $query->getHints();

        $this->assertCount(2, $hints);
        $this->assertEquals([AccessRuleWalker::class], $hints['doctrine.customTreeWalkers']);

        $context = new AccessRuleWalkerContext($this->accessRuleExecutor, 'VIEW', null);
        $this->assertEquals($context, $hints['oro_access_rule.context']);
        $this->assertSame($this->cache, $query->getQueryCacheDriver()->getPool());
    }

    public function testApplyToQueryWithDefaultConfigurationAndToken()
    {
        $user = new User(1);
        $org = new Organization(2);
        $token = new UsernamePasswordOrganizationToken($user, 'main', $org);
        $this->tokenStorage->setToken($token);

        $query = new Query($this->em);

        $this->helper->apply($query);
        $hints = $query->getHints();

        $this->assertCount(2, $hints);
        $this->assertEquals([AccessRuleWalker::class], $hints['doctrine.customTreeWalkers']);

        $context = new AccessRuleWalkerContext(
            $this->accessRuleExecutor,
            'VIEW',
            User::class,
            $user->getId(),
            $org->getId()
        );
        $this->assertEquals($context, $hints['oro_access_rule.context']);
        $this->assertSame($this->cache, $query->getQueryCacheDriver()->getPool());
    }

    public function testApplyToQueryWithDefaultConfigurationAndTokenWithOrganizationButWithoutUserObject()
    {
        $org = new Organization(2);
        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $user = $this->createMock(UserInterface::class);
        $token->expects($this->any())
            ->method('getOrganization')
            ->willReturn($org);
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->setToken($token);

        $query = new Query($this->em);

        $this->helper->apply($query);
        $hints = $query->getHints();

        $this->assertCount(2, $hints);
        $this->assertEquals([AccessRuleWalker::class], $hints['doctrine.customTreeWalkers']);

        $context = new AccessRuleWalkerContext(
            $this->accessRuleExecutor,
            'VIEW',
            null,
            null,
            $org->getId()
        );
        $this->assertEquals($context, $hints['oro_access_rule.context']);
        $this->assertSame($this->cache, $query->getQueryCacheDriver()->getPool());
    }

    public function testApplyToQueryWithCustomOptions()
    {
        $query = new Query($this->em);
        $this->helper->apply($query, 'VIEW', ['option1' => true, 'option2' => [3, 2, 1]]);
        $hints = $query->getHints();

        $this->assertCount(2, $hints);
        $this->assertEquals([AccessRuleWalker::class], $hints['doctrine.customTreeWalkers']);

        $context = new AccessRuleWalkerContext($this->accessRuleExecutor, 'VIEW', null);
        $context->setOption('option1', true);
        $context->setOption('option2', [3, 2, 1]);
        $this->assertEquals($context, $hints['oro_access_rule.context']);
        $this->assertSame($this->cache, $query->getQueryCacheDriver()->getPool());
    }
}
