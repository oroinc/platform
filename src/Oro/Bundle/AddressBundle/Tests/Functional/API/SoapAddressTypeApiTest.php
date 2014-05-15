<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 */
class SoapAddressTypeApiTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->initSoapClient();
    }

    /**
     * @return array
     */
    public function testGetAddressTypes()
    {
        $result = $this->soapClient->getAddressTypes();
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
            $result = $this->soapClient->getAddressType($addressType['name']);
            $result = $this->valueToArray($result);
            $this->assertNotEmpty($result);
            $this->assertEquals($addressType, $result);
        }
    }
}
