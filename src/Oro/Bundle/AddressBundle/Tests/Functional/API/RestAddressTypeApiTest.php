<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RestAddressTypeApiTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
    }

    /**
     * @return array
     */
    public function testGetAddressTypes()
    {
        $this->client->request('GET', $this->getUrl('oro_api_get_addresstypes'));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);

        return $result;
    }

    /**
     * @depends testGetAddressTypes
     * @param array $expected
     */
    public function testGetAddressType(array $expected)
    {
        foreach ($expected as $addressType) {
            $this->client->request(
                'GET',
                $this->getUrl('oro_api_get_addresstype', array('name' => $addressType['name']))
            );

            $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
            $this->assertNotEmpty($result);
            $this->assertEquals($addressType, $result);
        }
    }
}
