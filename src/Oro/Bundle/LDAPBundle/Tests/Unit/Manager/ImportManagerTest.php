<?php

namespace Oro\Bundle\LDAPBundle\Tests\Unit\Manager;

use FR3D\LdapBundle\Driver\LdapDriverException;

use Oro\Bundle\LDAPBundle\Manager\ImportManager;

class ImportManagerTest extends \PHPUnit_Framework_TestCase
{
    private $em;

    private $ldapManager;
    private $userManager;
    private $registry;
    private $userProvider;

    private $importManager;

    public function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->ldapManager = $this->getMockBuilder('Oro\Bundle\LDAPBundle\LDAP\LdapManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userManager = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\UserManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userProvider = $this->getMockBuilder('Oro\Bundle\LDAPBundle\Provider\UserProvider')
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->importManager = new ImportManager(
            $this->ldapManager,
            $this->userManager,
            $this->registry,
            $this->userProvider
        );
    }

    public function testImport()
    {
        $users = [
            [
                'cn'   => ['user1'],
                'mail' => ['sth@example.com'],
                'sn'   => ['surname'],
                'dn'   => 'cn=user1,dn=local',
            ],
            'count' => 1,
        ];

        $user = $this->getMock('Oro\Bundle\LDAPBundle\Tests\Unit\Stub\TestingUser');
        $user->expects($this->once())
            ->method('setPassword')
            ->with('');
        $this->ldapManager->expects($this->once())
            ->method('findUsers')
            ->will($this->returnValue($users));
        $this->ldapManager->expects($this->once())
            ->method('getUsernameAttr')
            ->will($this->returnValue('cn'));
        $this->ldapManager->expects($this->once())
            ->method('hydrate')
            ->with($user, $users[0]);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroUserBundle:User')
            ->will($this->returnValue($this->em));
        $this->userProvider->expects($this->once())
            ->method('findUsersByUsernames')
            ->with(['user1'])
            ->will($this->returnValue([]));
        $this->userProvider->expects($this->once())
            ->method('getNumberOfUsersByUsernames')
            ->with(['user1'])
            ->will($this->returnValue(0));
        $this->userManager->expects($this->once())
            ->method('createUser')
            ->will($this->returnValue($user));

        $expectedResult = [
            'add'      => 1,
            'replace'  => 0,
            'total'    => 1,
            'imported' => true,
            'errors'   => [],
        ];

        $result = $this->importManager->import(false);
        $this->assertEquals($result, $expectedResult);
    }

    public function testImportLdapDriverException()
    {
        $this->ldapManager->expects($this->once())
            ->method('findUsers')
            ->will($this->returnCallback(function () {
                throw new LdapDriverException();
            }));

        $expectedResult = [
            'add'      => 0,
            'replace'  => 0,
            'total'    => 0,
            'imported' => false,
            'errors'   => [
                'oro.ldap.import_users.error'
            ],
        ];

        $result = $this->importManager->import(false);
        $this->assertEquals($result, $expectedResult);
    }

    public function testImportDryRun()
    {
        $users = [
            [
                'cn'   => ['user1'],
                'mail' => ['sth@example.com'],
                'sn'   => ['surname'],
                'dn'   => 'cn=user1,dn=local',
            ],
            'count' => 1,
        ];

        $this->ldapManager->expects($this->once())
            ->method('findUsers')
            ->will($this->returnValue($users));
        $this->ldapManager->expects($this->once())
            ->method('getUsernameAttr')
            ->will($this->returnValue('cn'));
        $this->userProvider->expects($this->once())
            ->method('getNumberOfUsersByUsernames')
            ->with(['user1'])
            ->will($this->returnValue(0));

        $expectedResult = [
            'add'      => 1,
            'replace'  => 0,
            'total'    => 1,
            'imported' => false,
            'errors'   => [],
        ];

        $result = $this->importManager->import(true);
        $this->assertEquals($result, $expectedResult);
    }
}
