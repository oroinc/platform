<?php
namespace Oro\Bundle\SecurityBundle\Tests\Unit\ORM\Walker;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalker;
use Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalkerContext;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AclHelperTest extends TestCase
{
    /** @var AclHelper */
    private $helper;

    /** @var MockObject */
    private $container;

    /** @var MockObject */
    private $em;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    protected function setUp()
    {
        $this->em = $this->createMock(EntityManager::class);
        $configuration = $this->createMock(Configuration::class);
        $this->em->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration);
        $configuration->expects($this->any())
            ->method('getDefaultQueryHints')
            ->willReturn([]);

        $this->tokenStorage = new TokenStorage();

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['security.token_storage', 1, $this->tokenStorage]
            ]);
        $this->helper = new AclHelper($this->container);
    }

    public function testApplyToQueryWithDefaultConfiguration()
    {
        $query = new Query($this->em);

        $this->helper->apply($query);
        $hints = $query->getHints();

        $this->assertCount(2, $hints);
        $this->assertEquals([AccessRuleWalker::class], $hints['doctrine.customTreeWalkers']);

        $context = new AccessRuleWalkerContext($this->container, 'VIEW', null);
        $this->assertEquals($context, $hints['oro_access_rule.context']);
    }

    public function testApplyToQueryWithDefaultConfigurationAndToken()
    {
        $user = new User(1);
        $org = new Organization(2);
        $token = new UsernamePasswordOrganizationToken($user, '', 'main', $org);
        $this->tokenStorage->setToken($token);

        $query = new Query($this->em);

        $this->helper->apply($query);
        $hints = $query->getHints();

        $this->assertCount(2, $hints);
        $this->assertEquals([AccessRuleWalker::class], $hints['doctrine.customTreeWalkers']);

        $context = new AccessRuleWalkerContext(
            $this->container,
            'VIEW',
            User::class,
            $user->getId(),
            $org->getId()
        );
        $this->assertEquals($context, $hints['oro_access_rule.context']);
    }

    public function testApplyToQueryWithCustomOptions()
    {
        $query = new Query($this->em);
        $this->helper->apply($query, 'VIEW', ['option1' => true, 'option2' => [3, 2, 1]]);
        $hints = $query->getHints();

        $this->assertCount(2, $hints);
        $this->assertEquals([AccessRuleWalker::class], $hints['doctrine.customTreeWalkers']);

        $context = new AccessRuleWalkerContext($this->container, 'VIEW', null);
        $context->setOption('option1', true);
        $context->setOption('option2', [3, 2, 1]);
        $this->assertEquals($context, $hints['oro_access_rule.context']);
    }
}
