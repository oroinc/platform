<?php

namespace Oro\Bundle\AddressBundle\Migrations\Data\ORM;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;
use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use Symfony\Component\Yaml\Yaml;

/**
 * Imports country and regions data into the application. Includes translations for the English language.
 */
class LoadCountryData extends AbstractTranslatableEntityFixture implements VersionedFixtureInterface
{
    private const COUNTRY_PREFIX = 'country';
    private const REGION_PREFIX  = 'region';

    #[\Override]
    public function getVersion(): string
    {
        return '1.5';
    }

    #[\Override]
    protected function loadEntities(ObjectManager $manager): void
    {
        $this->loadCountriesAndRegions($manager, $this->getDataFromFile($this->getFileName()));
    }

    private function getFileName(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroAddressBundle/Migrations/Data/ORM/data/countries.yml');
    }

    private function isFileAvailable(string $fileName): bool
    {
        return is_file($fileName) && is_readable($fileName);
    }

    private function getDataFromFile(string $fileName): array
    {
        if (!$this->isFileAvailable($fileName)) {
            throw new \LogicException('File ' . $fileName . 'is not available');
        }

        $fileName = realpath($fileName);

        return Yaml::parse(file_get_contents($fileName));
    }

    private function getCountry(EntityRepository $countryRepository, string $locale, array $countryData): ?Country
    {
        if (empty($countryData['iso2Code']) || empty($countryData['iso3Code'])) {
            return null;
        }

        /** @var Country $country */
        $country = $countryRepository->findOneBy(['iso2Code' => $countryData['iso2Code']]);
        if (!$country) {
            $country = new Country($countryData['iso2Code']);
            $country->setIso3Code($countryData['iso3Code']);
        }

        $country->setLocale($locale);
        $country->setName($this->translate($countryData['iso2Code'], self::COUNTRY_PREFIX, $locale));

        return $country;
    }

    private function getRegion(
        EntityRepository $regionRepository,
        string $locale,
        Country $country,
        array $regionData
    ): ?Region {
        if (empty($regionData['combinedCode']) || empty($regionData['code'])) {
            return null;
        }

        /** @var Region $region */
        $region = $regionRepository->findOneBy(['combinedCode' => $regionData['combinedCode']]);
        if (!$region) {
            $region = new Region($regionData['combinedCode']);
            $region->setCode($regionData['code']);
            $region->setCountry($country);
        }

        $region->setLocale($locale);
        $region->setName($this->translate($regionData['combinedCode'], self::REGION_PREFIX, $locale));

        return $region;
    }

    private function loadCountriesAndRegions(ObjectManager $manager, array $countries): void
    {
        $countryRepository = $manager->getRepository(Country::class);
        $regionRepository = $manager->getRepository(Region::class);
        $translationLocales = $this->getTranslationLocales();
        foreach ($translationLocales as $locale) {
            foreach ($countries as $countryData) {
                $country = $this->getCountry($countryRepository, $locale, $countryData);
                if (!$country) {
                    continue;
                }

                $manager->persist($country);

                if (!empty($countryData['regions'])) {
                    foreach ($countryData['regions'] as $regionData) {
                        $region = $this->getRegion($regionRepository, $locale, $country, $regionData);
                        if (!$region) {
                            continue;
                        }

                        $manager->persist($region);
                    }
                }
            }
            $manager->flush();
            $manager->clear();
        }
    }
}
