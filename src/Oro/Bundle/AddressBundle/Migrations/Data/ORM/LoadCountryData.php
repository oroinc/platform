<?php

namespace Oro\Bundle\AddressBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;
use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadCountryData extends AbstractTranslatableEntityFixture implements
    VersionedFixtureInterface,
    ContainerAwareInterface
{
    const COUNTRY_PREFIX = 'country';
    const REGION_PREFIX  = 'region';

    /**
     * @var EntityRepository
     */
    protected $countryRepository;

    /**
     * @var EntityRepository
     */
    protected $regionRepository;

    /**
     * @var string
     */
    protected $structureFileName = '/data/countries.yml';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.0';
    }

    /**
     * {@inheritdoc}
     */
    protected function loadEntities(ObjectManager $manager)
    {
        $fileName = $this->getFileName();
        $countries = $this->getDataFromFile($fileName);
        $this->loadCountriesAndRegions($manager, $countries);
    }

    /**
     * @return string
     */
    protected function getFileName()
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroAddressBundle/Migrations/Data/ORM' . $this->structureFileName);
    }

    /**
     * @param string $fileName
     * @return bool
     */
    protected function isFileAvailable($fileName)
    {
        return is_file($fileName) && is_readable($fileName);
    }

    /**
     * @param string $fileName
     * @return array
     * @throws \LogicException
     */
    protected function getDataFromFile($fileName)
    {
        if (!$this->isFileAvailable($fileName)) {
            throw new \LogicException('File ' . $fileName . 'is not available');
        }

        $fileName = realpath($fileName);

        return Yaml::parse(file_get_contents($fileName));
    }

    /**
     * @param string $locale
     * @param array $countryData
     * @return null|Country
     */
    protected function getCountry($locale, array $countryData)
    {
        if (empty($countryData['iso2Code']) || empty($countryData['iso3Code'])) {
            return null;
        }

        /** @var $country Country */
        $country = $this->countryRepository->findOneBy(array('iso2Code' => $countryData['iso2Code']));
        if (!$country) {
            $country = new Country($countryData['iso2Code']);
            $country->setIso3Code($countryData['iso3Code']);
        }

        $countryName = $this->translate($countryData['iso2Code'], static::COUNTRY_PREFIX, $locale);

        $country->setLocale($locale)
            ->setName($countryName);

        return $country;
    }

    /**
     * @param string $locale
     * @param Country $country
     * @param array $regionData
     * @return null|Region
     */
    protected function getRegion($locale, Country $country, array $regionData)
    {
        if (empty($regionData['combinedCode']) || empty($regionData['code'])) {
            return null;
        }

        /** @var $region Region */
        $region = $this->regionRepository->findOneBy(array('combinedCode' => $regionData['combinedCode']));
        if (!$region) {
            $region = new Region($regionData['combinedCode']);
            $region->setCode($regionData['code'])
                ->setCountry($country);
        }

        $regionName = $this->translate($regionData['combinedCode'], static::REGION_PREFIX, $locale);

        $region->setLocale($locale)
            ->setName($regionName);

        return $region;
    }

    /**
     * Load countries and regions to DB
     *
     * @param ObjectManager $manager
     * @param array $countries
     */
    protected function loadCountriesAndRegions(ObjectManager $manager, array $countries)
    {
        $this->countryRepository = $manager->getRepository('OroAddressBundle:Country');
        $this->regionRepository  = $manager->getRepository('OroAddressBundle:Region');

        $translationLocales = $this->getTranslationLocales();

        foreach ($translationLocales as $locale) {
            foreach ($countries as $countryData) {
                $country = $this->getCountry($locale, $countryData);
                if (!$country) {
                    continue;
                }

                $manager->persist($country);

                if (!empty($countryData['regions'])) {
                    foreach ($countryData['regions'] as $regionData) {
                        $region = $this->getRegion($locale, $country, $regionData);
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
