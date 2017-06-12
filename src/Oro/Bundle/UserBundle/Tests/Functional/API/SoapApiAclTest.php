<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @group soap
 */
class SoapApiAclTest extends WebTestCase
{
    const TEST_ROLE = 'ROLE_SUPER_ADMIN';
    const TEST_EDIT_ROLE = 'ROLE_USER';

    protected function setUp()
    {
        $this->markTestSkipped("API for new ACL isn't implemented");
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->initSoapClient();
    }

    /**
     * @return array
     */
    public function testGetAcls()
    {
        $result = $this->soapClient->getAclIds();
        $result = $this->valueToArray($result);
        $result = $result['item'];
        sort($result);
        return $result;
    }

    /**
     * @param array $acls
     * @depends testGetAcls
     */
    public function testGetAcl($acls)
    {
        $i = 0;
        foreach ($acls as $acl) {
            $result = $this->soapClient->getAcl($acl);
            $result = $this->valueToArray($result);
            $this->assertEquals($acl, $result['id']);
            $i++;
            if ($i % 10 == 0) {
                break;
            }
        }
    }

    /**
     * @param $acls
     * @return array
     * @depends testGetAcls
     */
    public function testGetRoleAcl($acls)
    {
        $role =  $this->soapClient->getRoleByName(self::TEST_ROLE);
        $role = $this->valueToArray($role);
        $result = $this->soapClient->getRoleAcl($role['id']);
        $result = $this->valueToArray($result);
        $result = $result['item'];
        sort($result);
        $this->assertEquals($acls, $result);
        return $result;
    }

    /**
     * @param $acls
     * @depends testGetRoleAcl
     */
    public function testGetUserAcl($acls)
    {
        $userId = $this->soapClient->getUserBy(array('item' => array('key' =>'username', 'value' =>'admin')));
        $userId = $this->valueToArray($userId);
        $result =  $this->soapClient->getUserAcl($userId['id']);
        $result = $this->valueToArray($result);
        $result = $result['item'];
        sort($result);
        $this->assertEquals($acls, $result);
    }

    public function testRemoveAclFromRole()
    {
        $role =  $this->soapClient->getRoleByName(self::TEST_EDIT_ROLE);
        $role = $this->valueToArray($role);

        $result =  $this->soapClient->getRoleAcl($role['id']);
        $result = $this->valueToArray($result);
        $expectedAcl = $result['item'];

        $tmpExpectedAcl = $expectedAcl;

        foreach ($expectedAcl as $key => $val) {
            // root resource will be deleted after any resource delete
            if (preg_match('/oro_address*/', $val) || $val == 'root') {
                unset($expectedAcl[ $key ]);
            }
        }
        sort($expectedAcl);

        $this->soapClient->removeAclFromRole($role['id'], 'oro_address');
        $result =  $this->soapClient->getRoleAcl($role['id']);
        $result = $this->valueToArray($result);
        $actualAcl = $result['item'];
        sort($actualAcl);
        $this->assertEquals($expectedAcl, $actualAcl);

        return $tmpExpectedAcl;
    }

    /**
     * @depends testRemoveAclFromRole
     * @param $expectedAcl
     * @return array
     */
    public function testAddAclToRole($expectedAcl)
    {
        $roleId = $this->soapClient->getRoleByName(self::TEST_EDIT_ROLE);
        $roleId = $this->valueToArray($roleId);

        $this->soapClient->addAclToRole($roleId['id'], 'oro_address');
        $this->soapClient->addAclToRole($roleId['id'], 'root');

        $result = $this->soapClient->getRoleAcl($roleId['id']);
        $result = $this->valueToArray($result);
        $actualAcl = $result['item'];
        sort($actualAcl);
        sort($expectedAcl);

        $this->assertEquals($expectedAcl, $actualAcl);

        return $actualAcl;
    }

    /**
     * @depends testAddAclToRole
     */
    public function testRemoveAclsFromRole($expectedAcl)
    {
        $this->markTestSkipped('BAP-1058');
        $roleId =  $this->soapClient->getRoleByName(self::TEST_EDIT_ROLE);
        $roleId = $this->valueToArray($roleId);

        $tmpExpectedAcl = $expectedAcl;

        foreach ($expectedAcl as $key => $val) {
            if (preg_match('/oro_address*/', $val) || $val == 'root'
                || in_array(
                    $val,
                    array(
                        'oro_security', 'oro_login', 'oro_login_check', 'oro_logout', 'oro_reset_check_email',
                        'oro_reset_controller', 'oro_reset_password', 'oro_reset_request', 'oro_reset_send_mail')
                )) {
                // root resource will be deleted after any resource delete
                unset($expectedAcl[ $key ]);
            }
        }
        sort($expectedAcl);

        $this->soapClient->removeAclsFromRole($roleId['id'], array('oro_security','oro_address'));

        $result = $this->soapClient->getRoleAcl($roleId['id']);
        $result = $this->valueToArray($result);
        $actualAcl = $result['item'];
        sort($actualAcl);

        $this->assertEquals($expectedAcl, $actualAcl);

        return $tmpExpectedAcl;
    }

    /**
     * @depends testRemoveAclsFromRole
     */
    public function testAddAclsToRole($expectedAcl)
    {
        $roleId =  $this->soapClient->getRoleByName(self::TEST_EDIT_ROLE);
        $roleId = $this->valueToArray($roleId);

        $this->soapClient->addAclsToRole($roleId['id'], array('oro_security','oro_address'));

        $result =  $this->soapClient->getRoleAcl($roleId['id']);
        $result = $this->valueToArray($result);
        $actualAcl = $result['item'];
        sort($actualAcl);

        $this->assertEquals($expectedAcl, $actualAcl);
    }
}
