<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Response;

class RestApiTest extends WebTestCase
{
    /**
     * @var array
     */
    protected $fixtureData = array(
        'business_unit' => array(
            'name' => 'BU Name',
            'phone' => '123-123-123',
            'website' => 'http://localhost',
            'email' => 'email@email.localhost',
            'fax' => '321-321-321',
            'appendUsers' => array(1),
        )
    );

    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
    }

    /**
     * Test POST
     *
     * @return string
     */
    public function testCreate()
    {
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_businessunit'),
            $this->fixtureData
        );

        $responseData = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertInternalType('array', $responseData);
        $this->assertArrayHasKey('id', $responseData);

        return $responseData['id'];
    }

    /**
     * Test GET
     *
     * @depends testCreate
     * @param string $id
     */
    public function testGets($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_businessunits')
        );

        $responseData = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $initialCount = $this->getCount();

        foreach ($responseData as $row) {
            if ($row['id'] == $id) {
                $this->assertEquals($this->fixtureData['business_unit']['name'], $row['name']);
                $this->assertEquals($this->fixtureData['business_unit']['phone'], $row['phone']);
                $this->assertEquals($this->fixtureData['business_unit']['fax'], $row['fax']);
                $this->assertEquals($this->fixtureData['business_unit']['email'], $row['email']);
                $this->assertEquals($this->fixtureData['business_unit']['website'], $row['website']);
                $this->assertArrayHasKey('organization', $row);
                $this->assertEquals(1, $row['organization']['id']);
                $this->assertNotEmpty($row['organization']['name']);
            }
        }

        $this->assertGreaterThan($initialCount, $this->getCount(), 'Created Business Unit is not in list');
    }

    /**
     * Test GET
     *
     * @depends testCreate
     * @param string $id
     */
    public function testGet($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_businessunit', array('id' => $id))
        );

        $responseData = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($responseData);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals($id, $responseData['id']);
        $this->assertEquals($this->fixtureData['business_unit']['name'], $responseData['name']);
        $this->assertEquals($this->fixtureData['business_unit']['phone'], $responseData['phone']);
        $this->assertEquals($this->fixtureData['business_unit']['fax'], $responseData['fax']);
        $this->assertEquals($this->fixtureData['business_unit']['email'], $responseData['email']);
        $this->assertEquals($this->fixtureData['business_unit']['website'], $responseData['website']);
        $this->assertArrayHasKey('organization', $responseData);
        $this->assertEquals(1, $responseData['organization']['id']);
        $this->assertNotEmpty($responseData['organization']['name']);
    }

    /**
     * Test PUT
     *
     * @depends testCreate
     * @param string $id
     */
    public function testUpdate($id)
    {
        $requestData = $this->fixtureData;
        $requestData['business_unit']['name'] = $requestData['business_unit']['name'] . '_updated';
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_businessunit', array('id' => $id)),
            $requestData
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);

        // open businessUnit by id
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_businessunit', array('id' => $id))
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEquals($requestData['business_unit']['name'], $result['name']);
    }

    /**
     * Test DELETE
     *
     * @depends testCreate
     * @param string $id
     */
    public function testDelete($id)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_businessunit', array('id' => $id))
        );

        /** @var $result Response */
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_businessunit', array('id' => $id))
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }
}
