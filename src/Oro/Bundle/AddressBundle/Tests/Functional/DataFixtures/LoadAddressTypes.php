<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\InitialFixtureInterface;

/**
 * Loads some address types from the database.
 */
class LoadAddressTypes extends AbstractFixture implements InitialFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $repository = $manager->getRepository(AddressType::class);
        $this->addReference('billing', $repository->find('billing'));
        $this->addReference('shipping', $repository->find('shipping'));
    }
}
