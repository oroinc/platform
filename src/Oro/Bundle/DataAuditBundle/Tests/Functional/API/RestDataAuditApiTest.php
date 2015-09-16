<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class RestDataAuditApiTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
    }

    /**
     * @return array
     */
    public function testPreconditions()
    {
        // create users
        $request = [
            'user' => [
                'username'      => 'user_' . mt_rand(),
                'email'         => 'test_' . mt_rand() . '@test.com',
                'enabled'       => '1',
                'plainPassword' => '1231231q',
                'namePrefix'    => 'Mr',
                'firstName'     => 'firstName',
                'middleName'    => 'middleName',
                'lastName'      => 'lastName',
                'nameSuffix'    => 'Sn.',
                'roles'         => ['2'],
                'owner'         => '1'
            ]
        ];

        $this->client->request('POST', $this->getUrl('oro_api_post_user'), $request);
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 201);

        return $request;
    }

    /**
     * @param array $response
     *
     * @return array
     * @depends testPreconditions
     */
    public function testGetAudits(array $response)
    {
        $this->client->request('GET', $this->getUrl('oro_api_get_audits'));
        $result       = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $resultActual = reset($result);

        $this->assertEquals('create', $resultActual['action']);
        $this->assertEquals('Oro\Bundle\UserBundle\Entity\User', $resultActual['object_class']);
        $this->assertEquals('Oro\Bundle\UserBundle\Entity\User', $resultActual['objectClass']);
        $this->assertEquals($response['user']['username'], $resultActual['object_name']);
        $this->assertEquals($response['user']['username'], $resultActual['objectName']);
        $this->assertEquals('admin', $resultActual['username']);
        $this->assertEquals($response['user']['username'], $resultActual['data']['username']['new']);
        $this->assertEquals($response['user']['email'], $resultActual['data']['email']['new']);
        $this->assertEquals($response['user']['enabled'], $resultActual['data']['enabled']['new']);
        $this->assertContains($resultActual['data']['roles']['new'], array('User', 'Sales Rep'));

        return $resultActual;
    }

    /**
     * @param array $audit
     *
     * @depends testGetAudits
     */
    public function testGetAuditsWithDateFilters($audit)
    {
        $loggedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $loggedAt->setTimestamp(strtotime($audit['loggedAt']));
        $loggedGTEFilter = '?loggedAt>='
            . urlencode($loggedAt->format(\DateTime::ISO8601));
        $loggedGTFilter  = '?loggedAt>'
            . urlencode($loggedAt->sub(new \DateInterval('PT1H'))->format(\DateTime::ISO8601));
        $loggedLTFilter  = '?loggedAt<'
            . urlencode($loggedAt->sub(new \DateInterval('P1D'))->format(\DateTime::ISO8601));

        $this->client->request('GET', $this->getUrl('oro_api_get_audits') . $loggedGTEFilter);
        $this->assertCount(1, $this->getJsonResponseContent($this->client->getResponse(), 200));

        $this->client->request('GET', $this->getUrl('oro_api_get_audits') . $loggedGTFilter);
        $this->assertCount(1, $this->getJsonResponseContent($this->client->getResponse(), 200));

        $this->client->request('GET', $this->getUrl('oro_api_get_audits') . $loggedLTFilter);
        $this->assertEmpty($this->getJsonResponseContent($this->client->getResponse(), 200));
    }

    /**
     * @dataProvider actionFilterProvider
     * @depends      testGetAudits
     *
     * @param string $filterField
     * @param string $filterOperator
     * @param string $filterValue
     * @param int    $expectedCount
     */
    public function testGetAuditsWithActionFilter($filterField, $filterOperator, $filterValue, $expectedCount)
    {
        $filterString = implode(['?', $filterField, $filterOperator, $filterValue]);

        $this->client->request('GET', $this->getUrl('oro_api_get_audits') . $filterString);
        $this->assertCount($expectedCount, $this->getJsonResponseContent($this->client->getResponse(), 200));
    }

    /**
     * @return array
     */
    public function actionFilterProvider()
    {
        return [
            'should filter by action and found "create" log entries'                          => [
                '$filterField'    => 'action',
                '$filterOperator' => '=',
                '$filterValue'    => 'create',
                '$expectedCount'  => 1,
            ],
            'should filter by action and not found "remove" actions'                          => [
                '$filterField'    => 'action',
                '$filterOperator' => '=',
                '$filterValue'    => 'remove',
                '$expectedCount'  => 0,
            ],
            'should filter by action and found all not equals "update"'                       => [
                '$filterField'    => 'action',
                '$filterOperator' => '<>',
                '$filterValue'    => 'update',
                '$expectedCount'  => 1,
            ],
            'should filter by objectClass and found entry for User entity'                    => [
                '$filterField'    => 'objectClass',
                '$filterOperator' => '=',
                '$filterValue'    => 'Oro_Bundle_UserBundle_Entity_User',
                '$expectedCount'  => 1,
            ],
            'should filter by objectClass and returns empty result if not existing one given' => [
                '$filterField'    => 'objectClass',
                '$filterOperator' => '=',
                '$filterValue'    => 'Oro_Bundle_DataAuditBundle_Entity_TestEntity',
                '$expectedCount'  => 0,
            ]
        ];
    }

    /**
     * @depends testGetAudits
     */
    public function testGetAuditsWithUserFilter()
    {
        $adminId = $this->getContainer()->get('doctrine')->getRepository('OroUserBundle:User')
            ->findOneBy(['username' => self::USER_NAME])->getId();

        $adminUserFilter    = '?user=' . $adminId;
        $nonAdminUserFilter = '?user=' . rand(++$adminId, ($adminId + 100));

        $this->client->request('GET', $this->getUrl('oro_api_get_audits') . $adminUserFilter);
        $this->assertCount(1, $this->getJsonResponseContent($this->client->getResponse(), 200));

        $this->client->request('GET', $this->getUrl('oro_api_get_audits') . $nonAdminUserFilter);
        $this->assertCount(0, $this->getJsonResponseContent($this->client->getResponse(), 200));
    }

    /**
     * @param array $audit
     *
     * @depends testGetAudits
     */
    public function testGetAudit(array $audit)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_audit', array('id' => $audit['id']))
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        unset($result['loggedAt']);
        unset($audit['loggedAt']);

        $this->assertEquals($audit, $result);
    }
}
