<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\MigrationBundle\Entity\DataFixture;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Save information about performed data fixtures
 */
class UpdateDataFixturesFixture extends AbstractFixture
{
    /**
     * @var FixtureInterface[]
     */
    private $fixtures = [];

    /**
     * Add data fixtures to be updated
     *
     * @param FixtureInterface $fixture
     */
    public function addFixture(FixtureInterface $fixture): void
    {
        $this->fixtures[\get_class($fixture)] = $fixture;
    }

    /**
     * @return FixtureInterface[]
     */
    public function getFixtures(): array
    {
        return $this->fixtures;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->fixtures) {
            return;
        }

        $loadedAt = new \DateTime('now', new \DateTimeZone('UTC'));

        $repository = $manager->getRepository(DataFixture::class);
        foreach ($this->fixtures as $className => $fixture) {
            $dataFixture = null;
            $version = null;

            if ($fixture instanceof VersionedFixtureInterface) {
                /** @var DataFixture $dataFixture */
                $dataFixture = $repository->findOneBy(['className' => $className]);
                $version = $fixture->getVersion();
            }

            if (!$dataFixture) {
                $dataFixture = new DataFixture();
                $dataFixture->setClassName($className);
                $manager->persist($dataFixture);
            }

            $dataFixture->setVersion($version);
            $dataFixture->setLoadedAt($loadedAt);
        }

        $manager->flush();
    }
}
