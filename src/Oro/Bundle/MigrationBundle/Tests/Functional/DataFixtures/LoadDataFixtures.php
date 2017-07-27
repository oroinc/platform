<?php

namespace Oro\Bundle\MigrationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\MigrationBundle\Entity\DataFixture;
use Symfony\Component\Yaml\Yaml;

class LoadDataFixtures extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getFixturesData();

        foreach ($data as $reference => $fixtureData) {
            $fixture = $this->createDataFixture($fixtureData);

            $this->setReference($reference, $fixture);
            $manager->persist($fixture);
        }

        $manager->flush();
    }

    /**
     * @param array          $fixtureData
     * @param \DateTime|null $now
     *
     * @return DataFixture
     */
    private function createDataFixture(array $fixtureData, \DateTime $now = null)
    {
        $fixture = new DataFixture();

        if (!$now) {
            $now = new \DateTime();
        }

        $fixture
            ->setClassName($fixtureData['className'])
            ->setLoadedAt($now);

        return $fixture;
    }

    /**
     * @return array
     */
    private function getFixturesData()
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/data_fixtures.yml'));
    }
}
