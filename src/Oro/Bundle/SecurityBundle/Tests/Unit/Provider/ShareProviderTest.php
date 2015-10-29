<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Provider;

use Symfony\Bridge\Doctrine\Tests\Fixtures\User;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

use Oro\Bundle\SecurityBundle\Provider\ShareProvider;

class ShareProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $sidRetrievalStrategy;

    /** @var ShareProvider */
    protected $shareProvider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->registry = $this->getMockBuilder('Symfony\Bridge\Doctrine\RegistryInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclProvider = $this->getMockBuilder('Symfony\Component\Security\Acl\Dbal\MutableAclProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->sidRetrievalStrategy = $this->getMockBuilder(
            'Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->shareProvider = new ShareProvider(
            $this->registry,
            $this->aclProvider,
            $this->sidRetrievalStrategy
        );
    }

    public function testIsObjectSharedWithUser()
    {
        $token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $object = $this->getMockBuilder('Symfony\Component\Security\Acl\Model\DomainObjectInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $acl = $this->getMockBuilder('Symfony\Component\Security\Acl\Model\AclInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $sid = $this->getMockBuilder('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $ace = $this->getMockBuilder('Symfony\Component\Security\Acl\Domain\Entry')
            ->disableOriginalConstructor()
            ->getMock();
        $aceSid = $this->getMockBuilder('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->willReturn($acl);
        $this->sidRetrievalStrategy->expects($this->once())
            ->method('getSecurityIdentities')
            ->willReturn([$sid]);
        $acl->expects($this->once())
            ->method('getObjectAces')
            ->willReturn([$ace]);
        $sid->expects($this->once())
            ->method('equals')
            ->willReturn(true);
        $ace->expects($this->once())
            ->method('getSecurityIdentity')
            ->willReturn($aceSid);

        $this->assertTrue($this->shareProvider->isObjectSharedWithUser($object, $token));
    }

    public function testIsObjectSharedWithUserSid()
    {
        $user = new User(1, 2, 'test_user');
        $object = $this->getMockBuilder('Symfony\Component\Security\Acl\Model\DomainObjectInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $acl = $this->getMockBuilder('Symfony\Component\Security\Acl\Model\AclInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $aceSid = new UserSecurityIdentity('test_user', 'Symfony\Bridge\Doctrine\Tests\Fixtures\User');
        $ace = $this->getMockBuilder('Symfony\Component\Security\Acl\Domain\Entry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->willReturn($acl);
        $acl->expects($this->once())
            ->method('getObjectAces')
            ->willReturn([$ace]);
        $ace->expects($this->once())
            ->method('getSecurityIdentity')
            ->willReturn($aceSid);

        $this->assertTrue($this->shareProvider->isObjectSharedWithUserSid($object, $user));
    }

    public function testHasUserSharedRecords()
    {
        $user = new User(1, 2, 'test_user');
        $repo = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Entity\Repository\AclSecurityIdentityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('hasAclEntry')
            ->willReturn(true);
        $manager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManager')
            ->willReturn($manager);

        $this->assertTrue($this->shareProvider->hasUserSidSharedRecords($user));
    }
}
