<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityListBundle\Migrations\Data\ORM\AddActivityListsData;
use Oro\Bundle\EmailBundle\Entity\Email;

/**
 * Add activity lists for Email entity.
 */
class AddEmailActivityLists extends AddActivityListsData implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\UpdateUserEntitiesWithOrganization',
            'Oro\Bundle\EmailBundle\Migrations\Data\ORM\UpdateEmailTemplateWithOrganization'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->addActivityListsForActivityClass(
            $manager,
            Email::class,
            'fromEmailAddress.owner',
            'fromEmailAddress.owner.organization'
        );
    }
}
