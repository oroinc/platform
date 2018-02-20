<?php

namespace Oro\Bundle\WindowsBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\WindowsBundle\Entity\WindowsState;

class LoadWindowsStateData extends AbstractFixture
{
    /** {@inheritdoc} */
    public function load(ObjectManager $manager)
    {
        $user = $manager->getRepository('OroUserBundle:User')->findOneBy(['username' => 'admin']);

        $state = new WindowsState();
        $state
            ->setUser($user)
            ->setData(
                [
                    'cleanUrl' => '/path',
                ]
            );

        $manager->persist($state);
        $manager->flush();

        $this->setReference('windows_state.admin', $state);
    }
}
