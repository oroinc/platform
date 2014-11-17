<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ActivityListBundle\Migrations\Data\ORM\AddActivityListsData;

class AddEmailActivityLists extends AddActivityListsData implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\EmailBundle\Migrations\Data\ORM\LoadDashboardData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->addActivityListsForActivityClass(
            $manager,
            'OroEmailBundle:Email',
            'fromEmailAddress.owner',
            'fromEmailAddress.owner.organization'
        );
    }
}
