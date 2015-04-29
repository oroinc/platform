<?php

namespace Oro\Bundle\LDAPBundle\Tests\Unit\LDAP;

use Oro\Bundle\LDAPBundle\LDAP\LdapManager;

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

    public function testUpdateOroConfiguration()
    {
        $this->cm->expects($this->once())
            ->method('get')
            ->with('oro_ldap.server_base_dn')
            ->will($this->returnValue('dc=domain,dc=local'));

        $this->driver->expects($this->once())
            ->method('search')
            ->with('dc=domain,dc=local');

        $this->ldapManager->updateOroConfiguration($this->cm);
        $this->ldapManager->findUserBy([]);
    }
}
