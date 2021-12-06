<?php

namespace Oro\Bundle\WindowsBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WindowsBundle\Entity\WindowsState;

class LoadWindowsStateData extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $manager->getRepository(User::class)->findOneBy(['username' => 'admin']);

        $state = new WindowsState();
        $state
            ->setUser($user)
            ->setData(['cleanUrl' => '/path']);

        $manager->persist($state);
        $manager->flush();

        $this->setReference('windows_state.admin', $state);
    }
}
