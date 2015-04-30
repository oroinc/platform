<?php

namespace Oro\Bundle\LDAPBundle\Tests\Unit\LDAP;

use Oro\Bundle\LDAPBundle\LDAP\LdapManager;
use Oro\Bundle\LDAPBundle\Tests\Unit\Stub\TestingUser;

class LdapManagerTest extends \PHPUnit_Framework_TestCase
{
    private $cm;

    private $driver;
    private $ldapManager;

    public function setUp()
    {
        $this->cm = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->driver = $this->getMock('FR3D\LdapBundle\Driver\LdapDriverInterface');

        $attributes = [
            'filter' => '*',
            'attributes' => [
                ['ldap_attr' => 'attr'],
            ],
        ];

        $this->ldapManager = new LdapManager($this->driver, null, $attributes);
    }

    public function testUpdateOroConfigurationDn()
    {
        $this->cm->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['oro_ldap.server_base_dn'],
                ['oro_ldap.user_filter'],
                ['oro_ldap.user_mapping']
            )
            ->willReturnOnConsecutiveCalls(
                'dc=domain,dc=local',
                'objectClass=inetOrgPerson',
                []
            );

        $this->driver->expects($this->once())
            ->method('search')
            ->with('dc=domain,dc=local');

        $this->ldapManager->updateOroConfiguration($this->cm);
        $this->ldapManager->findUserBy([]);
    }

    public function testUpdateOroConfigurationAttributes()
    {
        $this->cm->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['oro_ldap.server_base_dn'],
                ['oro_ldap.user_filter'],
                ['oro_ldap.user_mapping']
            )
            ->willReturnOnConsecutiveCalls(
                'dc=domain,dc=local',
                'objectClass=inetOrgPerson',
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

        $user = new TestingUser();
        $this->ldapManager->updateOroConfiguration($this->cm);
        $this->ldapManager->hydrate($user, $entry);
        $this->assertEquals('user1', $user->getUsername());
        $this->assertEquals('sth@example.com', $user->getEmail());
        $this->assertEquals('cn=user1,dn=local', $user->getDn());
    }
}
