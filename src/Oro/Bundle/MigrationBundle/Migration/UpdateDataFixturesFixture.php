<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
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

        $this->checkEntityManagerOpen($manager);

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

                try {
                    $manager->persist($dataFixture);
                } catch (\Exception $e) {
                    throw new \RuntimeException(
                        sprintf(
                            'Exception during persisting the fixture "%s" with version "%s".',
                            $dataFixture->getClassName(),
                            $dataFixture->getVersion()
                        ),
                        $e->getCode(),
                        $e
                    );
                }
            }

            $dataFixture->setVersion($version);
            $dataFixture->setLoadedAt($loadedAt);
        }

        $manager->flush();
    }

    private function checkEntityManagerOpen(EntityManagerInterface $manager): void
    {
        if (!$manager->isOpen()) {
            throw new \RuntimeException('EntityManager is closed');
        }

        if ($manager->getConnection()->isRollbackOnly()) {
            throw new \RuntimeException('EntityManager\'s connection in rollback only state');
        }
    }
}
