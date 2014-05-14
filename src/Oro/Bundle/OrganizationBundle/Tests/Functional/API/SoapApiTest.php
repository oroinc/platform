<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class SoapApiTest extends WebTestCase
{
    /** @var Client */
    protected $client;

    protected $fixtureData = array(
        'business_unit' => array(
            'name' => 'BU Name',
            'organization' => '1',
            'phone' => '123-123-123',
            'website' => 'http://localhost',
            'email' => 'email@email.localhost',
            'fax' => '321-321-321',
            'owner' => '1',
        )
    );

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
     * Test POST
     * @return string
     */
    public function testCreate()
    {
        $id = $this->client->getSoapClient()->createBusinessUnit($this->fixtureData['business_unit']);
        $this->assertGreaterThan(0, $id);

        return $id;
    }
}
