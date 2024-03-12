<?php

namespace Oro\Bundle\WindowsBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\WindowsBundle\Entity\WindowsState;

class LoadWindowsStateData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadUser::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $state = new WindowsState();
        $state->setUser($this->getReference(LoadUser::USER));
        $state->setData(['cleanUrl' => '/path']);
        $this->setReference('windows_state.admin', $state);
        $manager->persist($state);
        $manager->flush();
    }
}
