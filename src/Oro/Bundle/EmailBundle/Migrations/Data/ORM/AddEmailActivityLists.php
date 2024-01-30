<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityListBundle\Migrations\Data\ORM\AddActivityListsData;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\UpdateUserEntitiesWithOrganization;

/**
 * Adds activity lists for Email entity.
 */
class AddEmailActivityLists extends AddActivityListsData implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            UpdateUserEntitiesWithOrganization::class,
            UpdateEmailTemplateWithOrganization::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $this->addActivityListsForActivityClass(
            $manager,
            Email::class,
            'fromEmailAddress.owner',
            'fromEmailAddress.owner.organization'
        );
    }
}
