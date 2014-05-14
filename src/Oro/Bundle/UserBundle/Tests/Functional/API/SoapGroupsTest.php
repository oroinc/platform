<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class SoapGroupsTest extends WebTestCase
{
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
     * @dataProvider groupsDataProvider
     */
    public function testCreateGroup(array $request)
    {
        $id = $this->client->getSoapClient()->createGroup($request);
        $this->assertInternalType('int', $id);
        $this->assertGreaterThan(0, $id);
    }

    /**
     * @depends testCreateGroup
     */
    public function testGetGroups()
    {
        $groups = $this->client->getSoapClient()->getGroups();
        $groups = $this->valueToArray($groups);
        $this->assertEquals(6, count($groups['item']));
    }

    /**
     * @param array $request
     * @param array $response
     *
     * @dataProvider groupsDataProvider
     * @depends testCreateGroup
     */
    public function testUpdateGroup(array $request, array $response)
    {
        $groups = $this->client->getSoapClient()->getGroups();
        $groups = $this->valueToArray($groups);

        $group = array_filter(
            $groups['item'],
            function ($a) use ($request) {
                return $a['name'] === $request['name'];
            }
        );

        $this->assertNotEmpty($group, 'Created group is not in groups list');

        $groupId = reset($group)['id'];

        $request['name'] .= '_Updated';

        $result = $this->client->getSoapClient()->updateGroup($groupId, $request);
        $this->assertEquals($response['return'], $result);

        $group = $this->client->getSoapClient()->getGroup($groupId);
        $group = $this->valueToArray($group);
        $this->assertEquals($request['name'], $group['name']);
    }

    /**
     * @depends testGetGroups
     */
    public function testDeleteGroups()
    {
        $groups = $this->client->getSoapClient()->getGroups();
        $groups = $this->valueToArray($groups);
        $this->assertEquals(6, count($groups['item']));

        foreach ($groups['item'] as $k => $group) {
            if ($k > 1) {
                //do not delete default groups
                $result = $this->client->getSoapClient()->deleteGroup($group['id']);
                $this->assertTrue($result);
            }
        }

        $groups = $this->client->getSoapClient()->getGroups();
        $groups = $this->valueToArray($groups);
        $this->assertEquals(2, count($groups['item']));
    }

    /**
     * @return array
     */
    public function groupsDataProvider()
    {
        return $this->getApiRequestsData(__DIR__ . DIRECTORY_SEPARATOR . 'GroupRequest');
    }
}
