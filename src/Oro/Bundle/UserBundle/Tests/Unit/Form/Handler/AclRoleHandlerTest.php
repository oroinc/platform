<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

class AclRoleHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclPrivilegeRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $privilegeRepository;

    /** @var AclManager */
    private $aclManager;

    /** @var AclRoleHandler */
    private $handler;

    protected function setUp(): void
    {
        $factory = $this->createMock(FormFactory::class);
        $aclCache = $this->createMock(AclCacheInterface::class);
        $this->privilegeRepository = $this->createMock(AclPrivilegeRepository::class);
        $this->aclManager = $this->createMock(AclManager::class);

        $this->handler = new AclRoleHandler($factory, $aclCache, []);
        $this->handler->setAclPrivilegeRepository($this->privilegeRepository);
        $this->handler->setAclManager($this->aclManager);
    }

    public function testAddExtensionFilter()
    {
        self::assertEmpty(ReflectionUtil::getPropertyValue($this->handler, 'extensionFilters'));

        $actionKey = 'action';
        $entityKey = 'entity';

        $defaultGroup = 'default';

        $this->handler->addExtensionFilter($actionKey, $defaultGroup);
        $this->handler->addExtensionFilter($entityKey, $defaultGroup);

        $expectedFilters = [
            $actionKey => [$defaultGroup],
            $entityKey => [$defaultGroup],
        ];
        self::assertEquals($expectedFilters, ReflectionUtil::getPropertyValue($this->handler, 'extensionFilters'));

        // each group added only once
        $this->handler->addExtensionFilter($actionKey, $defaultGroup);
        $this->handler->addExtensionFilter($entityKey, $defaultGroup);

        self::assertEquals($expectedFilters, ReflectionUtil::getPropertyValue($this->handler, 'extensionFilters'));
    }

    public function testGetAllPrivilegesUseAclGroup()
    {
        $privilege1 = new AclPrivilege();
        $privilege2 = new AclPrivilege();
        $role = $this->createMock(AbstractRole::class);
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $this->aclManager->expects(self::once())
            ->method('getSid')
            ->with($role)
            ->willReturn($sid);
        $this->privilegeRepository->expects(self::once())
            ->method('getPrivileges')
            ->with($sid, AclGroupProviderInterface::DEFAULT_SECURITY_GROUP)
            ->willReturn(new ArrayCollection([$privilege1, $privilege2]));

        self::assertEquals([], $this->handler->getAllPrivileges($role));
    }
}
