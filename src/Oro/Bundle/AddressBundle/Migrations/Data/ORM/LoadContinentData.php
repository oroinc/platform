<?php

namespace Oro\Bundle\AddressBundle\Migrations\Data\ORM;

use Oro\Bundle\AddressBundle\Entity\Continent;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;
use Symfony\Component\Yaml\Yaml;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use Oro\Bundle\AddressBundle\Entity\Country;

class LoadContinentData extends AbstractTranslatableEntityFixture implements VersionedFixtureInterface
{
    /**
     * @var string
     */
    protected $structureFileName = '/data/continents.yml';

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
        $continents = $this->getDataFromFile($fileName);
        $this->loadContinents($manager, $continents);
    }

    /**
     * @return string
     */
    protected function getFileName()
    {
        $fileName = __DIR__ . $this->structureFileName;
        $fileName = str_replace('/', DIRECTORY_SEPARATOR, $fileName);

        return $fileName;
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

        return Yaml::parse($fileName);
    }

    /**
     * Load continents to DB
     *
     * @param ObjectManager $manager
     * @param array $continents
     */
    protected function loadContinents(ObjectManager $manager, array $continents)
    {
        $countryRepository = $manager->getRepository('OroAddressBundle:Country');

        foreach ($continents as $continentData) {
            $continent = new Continent($continentData['code']);
            $continent->setName($continentData['name']);
            $manager->persist($continent);

            foreach ($continentData['countries'] as $countryCode) {
                $country = $countryRepository->findOneBy(array('iso2Code' => $countryCode));
                if ($country instanceof Country) {
                    $country->setContinent($continent);
                }
            }
        }

        $manager->flush();
        $manager->clear();
    }
}
