<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\ConfigBundle\Config\UserScopeManager;

class UserScopeManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserScopeManager
     */
    protected $object;

    /**
     * @var ObjectRepository
     */
    protected $repository;

    /**
     * @var SecurityContextInterface
     */
    protected $security;

    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $settings = [
        'oro_user' => [
            'level' => [
                'value' => 20,
                'type' => 'scalar',
            ],
        ],
    ];

    protected function setUp()
    {
        $repo = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Entity\Repository\ConfigRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->any())
            ->method('loadSettings');
        $this->om = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->om->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repo));
        $this->object = new UserScopeManager($this->om);

        $this->security = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->security
            ->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token
            ->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($this->getUser()));

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'security.context',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->security,
                        ],
                    ]
                )
            );

        $this->object = new UserScopeManager($this->om);
        $this->object->setContainer($this->container);
    }

    /**
     * @return User
     */
    protected function getUser()
    {
        $user = new User();

        /** @var \PHPUnit_Framework_MockObject_MockObject|Group $group1 */
        $group1 = $this->getMock('Oro\Bundle\UserBundle\Entity\Group');
        /** @var \PHPUnit_Framework_MockObject_MockObject|Group $group2 */
        $group2 = $this->getMock('Oro\Bundle\UserBundle\Entity\Group');

        $group1
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(2));

        $group2
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(3));

        $user
            ->setId(1)
            ->addGroup($group1)
            ->addGroup($group2);

        return $user;
    }

    public function testSecurity()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|UserScopeManager $object */
        $object = $this->getMock(
            'Oro\Bundle\ConfigBundle\Config\UserScopeManager',
            ['loadStoredSettings'],
            [$this->om]
        );

        $object->expects($this->never())->method('loadStoredSettings');

        $object->setContainer($this->container);

        $this->assertEquals('user', $object->getScopedEntityName());
    }

    public function testSecurityDirect()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|UserScopeManager $object */
        $object = $this->getMock(
            'Oro\Bundle\ConfigBundle\Config\UserScopeManager',
            ['loadStoredSettings'],
            [$this->om]
        );

        $object->expects($this->exactly(3))->method('loadStoredSettings');

        $object->setContainer($this->container);
        $object->setSecurity($this->security);

        $this->assertEquals('user', $object->getScopedEntityName());
    }

    public function testGetScopedEntityName()
    {
        $this->assertEquals('user', $this->object->getScopedEntityName());
    }

    public function testSetScopeId()
    {
        $object = clone $this->object;
        $object->setContainer($this->container);
        $object->setScopeId();
        $this->assertEquals(1, $object->getScopeId());
    }

    public function testGetScopeId()
    {
        $object = clone $this->object;
        $object->setContainer($this->container);
        $this->assertEquals(1, $object->getScopeId());
    }
}
