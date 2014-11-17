<?php
/**
 * Created by PhpStorm.
 * User: yurio
 * Date: 17.11.14
 * Time: 14:03
 */

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
            'fromEmailAddress.owner.owner',
            'fromEmailAddress.owner.organization'
        );
    }
} 