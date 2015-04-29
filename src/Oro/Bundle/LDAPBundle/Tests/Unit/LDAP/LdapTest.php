<?php

namespace Oro\Bundle\LDAPBundle\Tests\Unit\LDAP;

use Oro\Bundle\LDAPBundle\LDAP\Ldap;

class LdapTest extends \PHPUnit_Framework_TestCase
{
    private $cm;

    public function setUp()
    {
        $this->cm = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testUpdateOroConfiguration()
    {
        $this->cm->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['oro_ldap.server_base_dn'],
                ['oro_ldap.server_hostname'],
                ['oro_ldap.server_port']
            )
            ->willReturnOnConsecutiveCalls(
                'dc=domain,dc=local',
                'domain.local',
                123
            );

        $ldap = new Ldap();
        $ldap->updateOroConfiguration($this->cm);

        $options = $ldap->getOptions();
        $this->assertEquals('dc=domain,dc=local', $options['baseDn']);
        $this->assertEquals('domain.local', $options['host']);
        $this->assertEquals(123, $options['port']);
    }
}
