<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\MigrationBundle\Entity\DataFixture;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Save information about performed data fixtures
 */
class UpdateDataFixturesFixture extends AbstractFixture
{
    /**
     * @deprecated Will be removed in version 4.2, use the "addFixture"/"getFixtures" methods instead
     */
    protected $dataFixturesClassNames;

    /**
     * @var FixtureInterface[]
     */
    private $fixtures = [];

    /**
     * @deprecated Will be removed in version 4.2, use the "addFixture" method instead
     */
    public function setDataFixtures($classNames)
    {
        @trigger_error(sprintf(
            'The "%s()" method is deprecated and will be removed in version 4.2'
            . ', use the "addFixture" method instead',
            __METHOD__
        ), E_USER_DEPRECATED);

        $this->dataFixturesClassNames = $classNames;
    }

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
        $this->checkEntityManagerOpen($manager);

        $this->deprecatedLoad($manager);

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

                try {
                    $manager->persist($dataFixture);
                } catch (\Exception $e) {
                    throw new \RuntimeException(
                        sprintf(
                            'Exception during persisting the fixture %s with version %s.',
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

    /**
     * @param ObjectManager $manager
     */
    private function deprecatedLoad(ObjectManager $manager)
    {
        if (!empty($this->dataFixturesClassNames)) {
            $loadedAt = new \DateTime('now', new \DateTimeZone('UTC'));
            foreach ($this->dataFixturesClassNames as $className => $version) {
                $dataFixture = null;
                if ($version !== null) {
                    $dataFixture = $manager
                        ->getRepository(DataFixture::class)
                        ->findOneBy(['className' => $className]);
                }
                if (!$dataFixture) {
                    $dataFixture = new DataFixture();
                    $dataFixture->setClassName($className);
                }

                $dataFixture
                    ->setVersion($version)
                    ->setLoadedAt($loadedAt);
                $manager->persist($dataFixture);

                unset($this->fixtures[$className]);
            }
            $manager->flush();
        }
    }

    private function checkEntityManagerOpen(EntityManager $manager)
    {
        if (!$manager->isOpen()) {
            throw new \RuntimeException('EntityManager is closed');
        }

        if ($manager->getConnection()->isRollbackOnly()) {
            throw new \RuntimeException('EntityManager\'s connection in rollback only state');
        }
    }
}
