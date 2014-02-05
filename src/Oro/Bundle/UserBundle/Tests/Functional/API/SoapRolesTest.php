<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\UserBundle\Entity\Role;

/**
 * @outputBuffering enabled
 * @db_isolation
 */
class SoapRolesTest extends WebTestCase
{
    /** Default value for role label */
    const DEFAULT_VALUE = 'ROLE_LABEL';

    /** @var Client */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient(array(), ToolsAPI::generateWsseHeader());
        $this->client->soap(
            "http://localhost/api/soap",
            array(
                'location' => 'http://localhost/api/soap',
                'soap_version' => SOAP_1_2
            )
        );
    }

    /**
     * @param string $request
     * @param array  $response
     *
     * @dataProvider requestsApi
     */
    public function testCreateRole($request, $response)
    {
        if (is_null($request['label'])) {
            $request['label'] = self::DEFAULT_VALUE;
        }

        $this->client->setServerParameters(ToolsAPI::generateWsseHeader());
        $id = $this->client->getSoap()->createRole($request);
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

        $this->client->setServerParameters(ToolsAPI::generateWsseHeader());
        $roleByName =  $this->client->getSoap()->getRoleByName('Manager');
        $roleByName = ToolsAPI::classToArray($roleByName);

        $this->assertEquals($managerRole->getLabel(), $roleByName['label']);
        $this->assertEquals($managerRole->getId(), $roleByName['id']);
    }

    /**
     * @expectedException \SoapFault
     * @expectedExceptionMessage Role "NonExistRole" can not be found
     */
    public function testGetRoleByNameException()
    {
        $this->client->setServerParameters(ToolsAPI::generateWsseHeader());
        $this->client->getSoap()->getRoleByName('NonExistRole');
    }

    /**
     * @param string $request
     * @param array  $response
     *
     * @dataProvider requestsApi
     * @depends testCreateRole
     */
    public function testUpdateRole($request, $response)
    {
        if (is_null($request['label'])) {
            $request['label'] = self::DEFAULT_VALUE;
        }

        //get role id
        $this->client->setServerParameters(ToolsAPI::generateWsseHeader());
        $roleId =  $this->client->getSoap()->getRoleByName($request['label']);
        $roleId = ToolsAPI::classToArray($roleId);
        $request['label'] .= '_Updated';

        $this->client->setServerParameters(ToolsAPI::generateWsseHeader());
        $result =  $this->client->getSoap()->updateRole($roleId['id'], $request);
        $result = ToolsAPI::classToArray($result);
        ToolsAPI::assertEqualsResponse($response, $result);

        $this->client->setServerParameters(ToolsAPI::generateWsseHeader());
        $role =  $this->client->getSoap()->getRole($roleId['id']);
        $role = ToolsAPI::classToArray($role);
        $this->assertEquals($request['label'], $role['label']);
    }

    /**
     * @depends testUpdateRole
     * @return array
     */
    public function testGetRoles()
    {
        //get roles
        $this->client->setServerParameters(ToolsAPI::generateWsseHeader());
        $roles =  $this->client->getSoap()->getRoles();
        $roles = ToolsAPI::classToArray($roles);
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
            $this->client->setServerParameters(ToolsAPI::generateWsseHeader());
            $result =  $this->client->getSoap()->deleteRole($role['id']);
            $this->assertTrue($result);
        }

        $this->client->setServerParameters(ToolsAPI::generateWsseHeader());
        $roles =  $this->client->getSoap()->getRoles();
        $roles = ToolsAPI::classToArray($roles);
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
     * Data provider for REST API tests
     *
     * @return array
     */
    public function requestsApi()
    {
        return ToolsAPI::requestsApi(__DIR__ . DIRECTORY_SEPARATOR . 'RoleRequest');
    }
}
