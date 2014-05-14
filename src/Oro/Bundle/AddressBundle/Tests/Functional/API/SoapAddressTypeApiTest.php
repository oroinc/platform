<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 */
class SoapAddressTypeApiTest extends WebTestCase
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
     * @return array
     */
    public function testGetAddressTypes()
    {
        $result = $this->client->getSoapClient()->getAddressTypes();
        $result = $this->valueToArray($result);
        if (is_array(reset($result['item']))) {
            $actualData = $result['item'];
        } else {
            $actualData[] = $result['item'];
        }
        $this->assertNotEmpty($actualData);

        return $actualData;
    }

    /**
     * @depends testGetAddressTypes
     * @param array $expected
     */
    public function testGetAddressType($expected)
    {
        foreach ($expected as $addressType) {
            $result = $this->client->getSoapClient()->getAddressType($addressType['name']);
            $result = $this->valueToArray($result);
            $this->assertNotEmpty($result);
            $this->assertEquals($addressType, $result);
        }
    }
}
