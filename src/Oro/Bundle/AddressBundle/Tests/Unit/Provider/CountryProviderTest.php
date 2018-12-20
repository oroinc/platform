<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Provider;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Repository\CountryRepository;
use Oro\Bundle\AddressBundle\Provider\CountryProvider;

class CountryProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CountryRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $repository;

    /** @var CountryProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->repository = $this->createMock(CountryRepository::class);
        $this->provider = new CountryProvider($this->repository);
    }

    public function testGetCountriesNames()
    {
        $this->repository->expects($this->once())
            ->method('getCountries')
            ->willReturn([
                (new Country('iso2Code1'))->setName('name1'),
                (new Country('iso2Code2'))->setName('name2'),
            ]);

        $this->assertEquals(
            [
                'name1' => 'iso2Code1',
                'name2' => 'iso2Code2',
            ],
            $this->provider->getCountriesNames()
        );
    }
}
