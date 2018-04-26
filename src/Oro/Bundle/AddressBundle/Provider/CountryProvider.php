<?php

namespace Oro\Bundle\AddressBundle\Provider;

use Oro\Bundle\AddressBundle\Entity\Repository\CountryRepository;

class CountryProvider
{
    /** @var CountryRepository */
    protected $repository;

    /**
     * @param CountryRepository $repository
     */
    public function __construct(CountryRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return array [<iso2code> => <Country Name>, ...]
     */
    public function getCountriesNames()
    {
        $countries = [];
        foreach ($this->repository->getCountries() as $country) {
            $countries[$country->getName()] = $country->getIso2Code();
        }

        return $countries;
    }
}
