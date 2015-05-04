<?php

namespace Oro\Bundle\LDAPBundle\Tests\Unit\LDAP;

use Oro\Bundle\LDAPBundle\LDAP\LdapManager;
use Oro\Bundle\LDAPBundle\Tests\Unit\Stub\TestingUser;
use Oro\Bundle\LDAPBundle\Tests\Unit\Stub\TestingRole;

class LdapManagerTest extends \PHPUnit_Framework_TestCase
{
    private $em;
    private $cm;

    private $registry;
    private $driver;
    private $ldapManager;

    public function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->cm = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->driver = $this->getMockBuilder('Oro\Bundle\LDAPBundle\LDAP\ZendLdapDriver')
            ->disableOriginalConstructor()
            ->getMock();

        $attributes = [
            'filter' => '*',
            'attributes' => [
                ['ldap_attr' => 'attr'],
            ],
        ];

        $this->ldapManager = new LdapManager($this->registry, $this->driver, null, $attributes);
    }

    public function testHydrate()
    {
        $this->cm->expects($this->exactly(9))
            ->method('get')
            ->withConsecutive(
                ['oro_ldap.server_base_dn'],
                ['oro_ldap.user_filter'],
                ['oro_ldap.role_filter'],
                ['oro_ldap.role_id_attribute'],
                ['oro_ldap.role_user_id_attribute'],
                ['oro_ldap.export_user_base_dn'],
                ['oro_ldap.export_user_class'],
                ['oro_ldap.role_mapping'],
                ['oro_ldap.user_mapping']
            )
            ->willReturnOnConsecutiveCalls(
                'dc=domain,dc=local',
                'objectClass=inetOrgPerson',
                'objectClass=groupOfNames',
                'cn',
                'member',
                'cn=admin,ou=users,dc=domain,dc=local',
                'inetOrgPerson',
                [
                    [
                        'ldapName' => 'role1',
                        'crmRoles' => [1],
                    ],
                ],
                [
                    'title'    => null,
                    'email'    => 'mail',
                    'username' => 'cn',
                ]
            );

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
        $this->ldapManager->updateOroConfiguration($this->cm);
        $this->ldapManager->hydrate($user, $entry);
        $this->assertEquals('user1', $user->getUsername());
        $this->assertEquals('sth@example.com', $user->getEmail());
        $this->assertEquals('cn=user1,dn=local', $user->getDn());
        $this->assertCount(1, $user->getRoles());
        $this->assertSame($role, $user->getRoles()[0]);
    }
}
