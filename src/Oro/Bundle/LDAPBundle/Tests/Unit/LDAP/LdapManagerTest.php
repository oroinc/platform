<?php

namespace Oro\Bundle\LDAPBundle\Tests\Unit\LDAP;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\DataGridBundle\Common\Object as ConfigObject;
use Oro\Bundle\LDAPBundle\LDAP\LdapManager;
use Oro\Bundle\LDAPBundle\Tests\Unit\Stub\TestingRole;
use Oro\Bundle\LDAPBundle\Tests\Unit\Stub\TestingUser;

class LdapManagerTest extends \PHPUnit_Framework_TestCase
{
    private $em;
    private $registry;
    private $driver;
    private $ldapManager;
    private $channel;
    private $transport;

    public function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em->expects($this->any())
            ->method('persist');

        $this->registry->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($this->em));

        $this->driver = $this->getMockBuilder('Oro\Bundle\LDAPBundle\LDAP\ZendLdapDriver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->transport = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Transport')
            ->disableOriginalConstructor()
            ->getMock();

        $this->transport->expects($this->any())
            ->method('getSettingsBag')
            ->will($this->returnValue(new ParameterBag([
                'server_hostname' => '127.0.0.1',
                'server_port' => 389,
                'server_encryption' => 'none',
                'server_base_dn' => 'dc=domain,dc=local',
                'admin_dn' => 'cn=admin,dc=domain,dc=local',
                'admin_password' => 'some-password',
            ])));

        $this->channel = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->channel->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($this->transport));

        $this->channel->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(5));

        $this->channel->expects($this->any())
            ->method('getMappingSettings')
            ->will($this->returnValue(ConfigObject::create([
                'user_filter' => 'objectClass=inetOrgPerson',
                'role_filter' => 'objectClass=groupOfNames',
                'role_id_attribute' => 'cn',
                'role_user_id_attribute' => 'member',
                'export_user_base_dn' => 'ou=users,dc=domain,dc=local',
                'export_user_class' => 'inetOrgPerson',
                'role_mapping' => [
                    [
                        'ldapName' => 'role1',
                        'crmRoles' => [1],
                    ],
                ],
                'user_mapping' => [
                    'title'    => null,
                    'email'    => 'mail',
                    'username' => 'cn',
                ]
            ])));

        $this->ldapManager = new LdapManager($this->registry, $this->driver, null, $this->channel);
    }

    public function testHydrate()
    {
        $entry = [
            'cn'   => ['user1'],
            'mail' => ['sth@example.com'],
            'sn'   => ['surname'],
            'dn'   => 'cn=user1,dn=local',
        ];
        $roleEntries = [
            [
                'member' => ['cn=user1,dn=local'],
                'cn'     => ['role1'],
            ],
        ];
        $role = new TestingRole('role1', 1);

        $this->em->expects($this->once())
            ->method('getReference')
            ->with('Oro\Bundle\UserBundle\Entity\Role', 1)
            ->will($this->returnValue($role));
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroUserBundle:Role')
            ->will($this->returnValue($this->em));
        $this->driver->expects($this->once())
            ->method('search')
            ->with('dc=domain,dc=local', '(&(objectClass=groupOfNames)(member=cn=user1,dn=local))')
            ->will($this->returnValue($roleEntries));

        $user = new TestingUser();
        $this->ldapManager->hydrate($user, $entry);
        $this->assertEquals('user1', $user->getUsername());
        $this->assertEquals('sth@example.com', $user->getEmail());
        $this->assertCount(1, $user->getRoles());
        $this->assertSame($role, $user->getRoles()[0]);
    }

    public function testSaveWithoutDn()
    {
        $expectedEntry = [
            'objectClass' => ['inetOrgPerson'],
            'cn'          => 'user1',
            'mail'        => 'email@example.com',
        ];

        $this->driver->expects($this->once())
            ->method('save')
            ->with('cn=user1,ou=users,dc=domain,dc=local', $expectedEntry);

        $user = new TestingUser();
        $user->setUsername('user1');
        $user->setEmail('email@example.com');

        $this->ldapManager->save($user);
    }

    public function testSaveWithDn()
    {
        $expectedEntry = [
            'objectClass' => ['inetOrgPerson'],
            'cn'          => 'user1',
            'mail'        => 'email@example.com',
        ];

        $this->driver->expects($this->once())
            ->method('save')
            ->with('cn=user1,ou=org,dc=domain,dc=local', $expectedEntry);

        $user = new TestingUser();
        $user->setUsername('user1');
        $user->setEmail('email@example.com');
        $user->setLdapMappings([5 => 'cn=user1,ou=org,dc=domain,dc=local']);

        $this->ldapManager->save($user);
    }
}
