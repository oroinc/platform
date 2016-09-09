<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @group soap
 */
class SoapApiTest extends WebTestCase
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
        )
    );

    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->initSoapClient();
    }

    /**
     * Test POST
     *
     * @return string
     */
    public function testCreate()
    {
        $id = $this->soapClient->createBusinessUnit($this->fixtureData['business_unit']);
        $this->assertGreaterThan(0, $id);

        return $id;
    }
}
