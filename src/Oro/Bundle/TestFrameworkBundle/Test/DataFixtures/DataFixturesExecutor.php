<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\SharedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Unlike to the original executor does not clear entity manager after each fixture.
 */
class DataFixturesExecutor extends ORMExecutor
{
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
        $fixture->load($manager);
    }
}
