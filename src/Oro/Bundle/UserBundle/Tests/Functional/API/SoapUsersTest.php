<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @group soap
 */
class SoapUsersTest extends WebTestCase
{
    /** Default value for role label */
    const DEFAULT_VALUE = 'USER_LABEL';

    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->initSoapClient();
    }

    /**
     * @param array $request
     * @dataProvider usersDataProvider
     */
    public function testCreateUser(array $request)
    {
        $id = $this->soapClient->createUser($request);
        $this->assertInternalType('int', $id);
        $this->assertGreaterThan(0, $id);
    }

    /**
     * @param array $request
     * @param array $response
     * @dataProvider usersDataProvider
     * @depends testCreateUser
     */
    public function testUpdateUser(array $request, array $response)
    {
        //get user id
        $userId = $this->soapClient->getUserBy(
            array('item' => array('key' =>'username', 'value' => $request['username']))
        );
        $userId = $this->valueToArray($userId);

        $request['username'] = 'Updated_' . $request['username'];
        $request['email'] = 'Updated_' . $request['email'];
        unset($request['plainPassword']);

        $result = $this->soapClient->updateUser($userId['id'], $request);
        $this->assertEquals($response['return'], $result);

        $user = $this->soapClient->getUser($userId['id']);
        $user = $this->valueToArray($user);
        $this->assertEquals($request['username'], $user['username']);
        $this->assertEquals($request['email'], $user['email']);
    }

    /**
     * @dataProvider usersDataProvider
     * @depends testUpdateUser
     */
    public function testGetUsers(array $request)
    {
        $users = $this->soapClient->getUsers(1, 1000);
        $users = $this->valueToArray($users);

        $user = array_filter(
            $users['item'],
            function ($a) use ($request) {
                return $a['username'] === 'Updated_' . $request['username'];
            }
        );

        $this->assertNotEmpty($user, 'Updated user is not in users list');

    }

    public function testGetUserRoles()
    {
        $roles = $this->soapClient->getUserRoles(1);
        $roles = $this->valueToArray($roles);
        $this->assertEquals('Administrator', $roles['item']['label']);
    }

    public function testGetUserGroups()
    {
        $groups = $this->soapClient->getUserGroups(1);
        $groups = $this->valueToArray($groups);
        $this->assertEquals('Administrators', $groups['item']['name']);
    }

    /**
     * @expectedException \SoapFault
     * @expectedExceptionMessage Empty filter data
     */
    public function testGetUserByException()
    {
        $this->soapClient->getUserBy();
    }

    /**
     * @dataProvider usersDataProvider
     * @depends testGetUsers
     * @expectedException \SoapFault
     * @expectedExceptionMessage User cannot be found using specified filter
     */
    public function testDeleteUser(array $request)
    {
        //get user id
        $user = $this->soapClient->getUserBy(
            array(
                'item' => array(
                    'key' =>'username',
                    'value' =>'Updated_' . $request['username'])
            )
        );
        $user = $this->valueToArray($user);

        $result = $this->soapClient->deleteUser($user['id']);
        $this->assertTrue($result);

        $this->soapClient->getUserBy(
            array(
                'item' => array(
                    'key' =>'username',
                    'value' =>'Updated_' . $request['username'])
            )
        );
    }

    /**
     * @expectedException \SoapFault
     * @expectedExceptionMessage An operation is forbidden. Reason: self delete
     */
    public function testSelfDeleteUser()
    {
        $this->soapClient->deleteUser(1);
    }

    /**
     * Data provider for REST API tests
     *
     * @return array
     */
    public function usersDataProvider()
    {
        return $this->getApiRequestsData(__DIR__ . DIRECTORY_SEPARATOR . 'UserRequest');
    }
}
