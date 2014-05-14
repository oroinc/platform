<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class SoapRolesTest extends WebTestCase
{
    /** Default value for role label */
    const DEFAULT_VALUE = 'ROLE_LABEL';

    /** @var Client */
    protected $client;

    public function setUp()
    {
        $this->client = self::createClient(array(), $this->generateWsseAuthHeader());
        $this->client->createSoapClient(
            "http://localhost/api/soap",
            array(
                'location' => 'http://localhost/api/soap',
                'soap_version' => SOAP_1_2
            )
        );
    }

    /**
     * @param array $request
     * @dataProvider rolesDataProvider
     */
    public function testCreateRole(array $request)
    {
        if (is_null($request['label'])) {
            $request['label'] = self::DEFAULT_VALUE;
        }

        $id = $this->client->getSoapClient()->createRole($request);
        $this->assertInternalType('int', $id);
        $this->assertGreaterThan(0, $id);

        return $id;
    }

    public function testGetRoleByName()
    {
        $this->client->getKernel()->boot();
        $roles = $this->client->getContainer()->get('doctrine.orm.entity_manager')->getRepository('OroUserBundle:Role');
        /** @var Role $managerRole */
        $managerRole = $roles->findOneBy(array('label' => 'Manager'));

        if (is_null($managerRole)) {
            $managerRole = $roles->findOneBy(array('label' => 'Marketing Manager'));
        }

        $roleByName = $this->client->getSoapClient()->getRoleByName($managerRole->getLabel());
        $roleByName = $this->valueToArray($roleByName);

        $this->assertEquals($managerRole->getLabel(), $roleByName['label']);
        $this->assertEquals($managerRole->getId(), $roleByName['id']);
    }

    /**
     * @expectedException \SoapFault
     * @expectedExceptionMessage Role "NonExistRole" can not be found
     */
    public function testGetRoleByNameException()
    {
        $this->client->getSoapClient()->getRoleByName('NonExistRole');
    }

    /**
     * @param array $request
     * @param array $response
     * @dataProvider rolesDataProvider
     * @depends testCreateRole
     */
    public function testUpdateRole(array $request, array $response)
    {
        if (is_null($request['label'])) {
            $request['label'] = self::DEFAULT_VALUE;
        }

        //get role id
        $roleId = $this->client->getSoapClient()->getRoleByName($request['label']);
        $roleId = $this->valueToArray($roleId);
        $request['label'] .= '_Updated';

        $result =  $this->client->getSoapClient()->updateRole($roleId['id'], $request);
        $this->assertEquals($response['return'], $result);

        $role = $this->client->getSoapClient()->getRole($roleId['id']);
        $role = $this->valueToArray($role);
        $this->assertEquals($request['label'], $role['label']);
    }

    /**
     * @depends testUpdateRole
     * @return array
     */
    public function testGetRoles()
    {
        //get roles
        $roles = $this->client->getSoapClient()->getRoles();
        $roles = $this->valueToArray($roles);
        //filter roles
        $roles = array_filter(
            $roles['item'],
            function ($v) {
                return strpos($v['label'], '_Updated') !== false;
            }
        );
        $this->assertEquals(3, count($roles));

        return $roles;
    }

    /**
     * @depends testGetRoles
     * @param array $roles
     */
    public function testDeleteRoles($roles)
    {
        //get roles
        foreach ($roles as $role) {
            $result =  $this->client->getSoapClient()->deleteRole($role['id']);
            $this->assertTrue($result);
        }

        $roles = $this->client->getSoapClient()->getRoles();
        $roles = $this->valueToArray($roles);
        if (!empty($roles)) {
            $roles = array_filter(
                $roles['item'],
                function ($v) {
                    return strpos($v['label'], '_Updated') !== false;
                }
            );
        }
        $this->assertEmpty($roles);
    }

    /**
     * @return array
     */
    public function rolesDataProvider()
    {
        return $this->getApiRequestsData(__DIR__ . DIRECTORY_SEPARATOR . 'RoleRequest');
    }
}
