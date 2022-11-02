<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RestAddressTypeApiTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
    }

    /**
     * @return array
     */
    public function testGetAddressTypes()
    {
        $this->client->jsonRequest('GET', $this->getUrl('oro_api_get_addresstypes'));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);

        return $result;
    }

    /**
     * @depends testGetAddressTypes
     */
    public function testGetAddressType(array $expected)
    {
        foreach ($expected as $addressType) {
            $this->client->jsonRequest(
                'GET',
                $this->getUrl('oro_api_get_addresstype', ['name' => $addressType['name']])
            );

            $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
            $this->assertEquals($addressType, $result);
        }

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_addresstype', ['name' => 'shipping'])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEquals(
            [
                'name'  => 'shipping',
                'label' => 'Shipping'
            ],
            $result
        );
    }
}
