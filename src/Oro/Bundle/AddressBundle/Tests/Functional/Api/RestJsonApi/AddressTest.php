<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class AddressTest extends RestJsonApiTestCase
{
    use AddressCountryAndRegionTestTrait;

    private const ENTITY_CLASS               = Address::class;
    private const ENTITY_TYPE                = 'addresses';
    private const IS_REGION_REQUIRED         = false;
    private const COUNTRY_REGION_ADDRESS_REF = 'address1';

    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(['@OroAddressBundle/Tests/Functional/DataFixtures/addresses.yml']);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'addresses']
        );

        $this->assertResponseContains('cget_address.yml', $response);
    }

    public function testGetListFilterByCountry()
    {
        $response = $this->cget(
            ['entity' => 'addresses'],
            ['filter' => ['country' => '<toString(@country_israel->iso2Code)>']]
        );

        $this->assertResponseContains('cget_address_filter_country.yml', $response);
    }

    public function testGetListFilterByRegion()
    {
        $response = $this->cget(
            ['entity' => 'addresses'],
            ['filter' => ['region' => '<toString(@region_israel_telaviv->combinedCode)>']]
        );

        $this->assertResponseContains('cget_address_filter_region.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'addresses', 'id' => '<toString(@address2->id)>']
        );

        $this->assertResponseContains('get_address.yml', $response);
    }

    public function testCreate()
    {
        $countryId = $this->getReference('country_usa')->getIso2Code();
        $regionId = $this->getReference('region_usa_california')->getCombinedCode();

        $response = $this->post(
            ['entity' => 'addresses'],
            'create_address.yml'
        );

        $addressId = (int)$this->getResourceId($response);
        $responseContent = $this->updateResponseContent('create_address.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var Address $address */
        $address = $this->getEntityManager()
            ->find(Address::class, $addressId);
        self::assertNotNull($address);
        self::assertEquals('New Address', $address->getLabel());
        self::assertEquals('Street 1', $address->getStreet());
        self::assertEquals('Street 2', $address->getStreet2());
        self::assertEquals('Los Angeles', $address->getCity());
        self::assertEquals('90001', $address->getPostalCode());
        self::assertEquals('Acme', $address->getOrganization());
        self::assertEquals('Mr.', $address->getNamePrefix());
        self::assertEquals('M.D.', $address->getNameSuffix());
        self::assertEquals('John', $address->getFirstName());
        self::assertEquals('Edgar', $address->getMiddleName());
        self::assertEquals('Doo', $address->getLastName());
        self::assertEquals($countryId, $address->getCountry()->getIso2Code());
        self::assertEquals($regionId, $address->getRegion()->getCombinedCode());
    }

    public function testCreateWithRequiredDataOnly()
    {
        $countryId = $this->getReference('country_usa')->getIso2Code();
        $regionId = $this->getReference('region_usa_california')->getCombinedCode();

        $data = $this->getRequestData('create_address_min.yml');
        $response = $this->post(
            ['entity' => 'addresses'],
            $data
        );

        $addressId = (int)$this->getResourceId($response);
        $responseContent = $data;
        $responseContent['data']['attributes']['label'] = null;
        $responseContent['data']['attributes']['street2'] = null;
        $responseContent['data']['attributes']['organization'] = null;
        $responseContent['data']['attributes']['namePrefix'] = null;
        $responseContent['data']['attributes']['nameSuffix'] = null;
        $responseContent['data']['attributes']['firstName'] = null;
        $responseContent['data']['attributes']['middleName'] = null;
        $responseContent['data']['attributes']['lastName'] = null;
        $this->assertResponseContains($responseContent, $response);

        /** @var Address $address */
        $address = $this->getEntityManager()
            ->find(Address::class, $addressId);
        self::assertNotNull($address);
        self::assertNull($address->getLabel());
        self::assertEquals('Street 1', $address->getStreet());
        self::assertNull($address->getStreet2());
        self::assertEquals('Los Angeles', $address->getCity());
        self::assertEquals('90001', $address->getPostalCode());
        self::assertNull($address->getOrganization());
        self::assertNull($address->getNamePrefix());
        self::assertNull($address->getNameSuffix());
        self::assertNull($address->getFirstName());
        self::assertNull($address->getMiddleName());
        self::assertNull($address->getLastName());
        self::assertEquals($countryId, $address->getCountry()->getIso2Code());
        self::assertEquals($regionId, $address->getRegion()->getCombinedCode());
    }

    public function testUpdate()
    {
        $addressId = $this->getReference('address2')->getId();
        $data = [
            'data' => [
                'type'       => 'addresses',
                'id'         => (string)$addressId,
                'attributes' => [
                    'label' => 'Updated Address'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'addresses', 'id' => (string)$addressId],
            $data
        );

        $this->assertResponseContains($data, $response);

        /** @var Address $address */
        $address = $this->getEntityManager()
            ->find(Address::class, $addressId);
        self::assertNotNull($address);
        self::assertEquals('Updated Address', $address->getLabel());
    }

    public function testDelete()
    {
        $addressId = $this->getReference('address2')->getId();

        $this->delete(
            ['entity' => 'addresses', 'id' => (string)$addressId]
        );

        $address = $this->getEntityManager()
            ->find(Address::class, $addressId);
        self::assertTrue(null === $address);
    }

    public function testDeleteList()
    {
        $addressId = $this->getReference('address2')->getId();

        $this->cdelete(
            ['entity' => 'addresses'],
            ['filter' => ['id' => (string)$addressId]]
        );

        $address = $this->getEntityManager()
            ->find(Address::class, $addressId);
        self::assertTrue(null === $address);
    }

    public function testTryToSetNullCountry()
    {
        $addressId = $this->getReference('address1')->getId();
        $data = [
            'data' => [
                'type'          => 'addresses',
                'id'            => (string)$addressId,
                'relationships' => [
                    'country' => [
                        'data' => null
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'addresses', 'id' => $addressId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/country/data']
            ],
            $response
        );
    }
}
