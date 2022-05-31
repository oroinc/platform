<?php

namespace Oro\Bundle\AddressBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Repository\CountryRepository;

/**
 * Provides a set of utility methods for country related grids.
 */
class CountryProvider
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @return array [country name => ISO2 country code, ...]
     */
    public function getCountryChoices(): array
    {
        $result = [];
        $countries = $this->getCountryRepository()->getCountries();
        foreach ($countries as $country) {
            $result[$country->getName()] = $country->getIso2Code();
        }

        return $result;
    }

    private function getCountryRepository(): CountryRepository
    {
        return $this->doctrine->getRepository(Country::class);
    }
}
