<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\Purger\ORMPurgerInterface;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Common\DataFixtures\SharedFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\ORM\Registry;

/**
 * This ORM executor does not clear the entity manager if a data fixture
 * implements \Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\InitialFixtureInterface
 * or Alice fixture have 'initial' parameter with 'true' value.
 */
class DataFixturesExecutor extends ORMExecutor
{
    /** @var ReferenceRepository[] */
    private array $referenceRepositories = [];

    private Registry $registry;

    public function __construct(
        EntityManagerInterface $em,
        Registry               $registry,
        ?ORMPurgerInterface    $purger = null,
    ) {
        parent::__construct($em, $purger);
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager, FixtureInterface $fixture)
    {
        if ($this->logger) {
            $prefix = '';
            if ($fixture instanceof OrderedFixtureInterface) {
                $prefix = sprintf('[%d] ', $fixture->getOrder());
            }
            $this->log('loading ' . $prefix . get_class($fixture));
        }
        // additionally pass the instance of reference repository to shared fixtures
        if ($fixture instanceof SharedFixtureInterface) {
            $fixture->setReferenceRepository($this->referenceRepository);
        }

        if ($fixture instanceof AbstractFixture) {
            $this->createReferenceRepositories();
            $fixture->setReferenceRepositories($this->getReferenceRepositories());
        }

        $fixture->load($manager);

        if (!$fixture instanceof InitialFixtureInterface) {
            if ($fixture instanceof AliceFileFixture && $fixture->isInitialFixture()) {
                return;
            }
            $manager->clear();
        }
    }

    /** @return ReferenceRepository[] */
    public function getReferenceRepositories(): array
    {
        return $this->referenceRepositories;
    }

    private function createReferenceRepositories(): void
    {
        if (!empty($this->referenceRepositories)) {
            return;
        }

        foreach ($this->registry->getManagers() as $objectManager) {
            if ($objectManager === $this->getObjectManager()) {
                $this->referenceRepositories[] = $this->referenceRepository;
                continue;
            }

            $this->referenceRepositories[] = new ReferenceRepository($objectManager);
        }
    }
}
