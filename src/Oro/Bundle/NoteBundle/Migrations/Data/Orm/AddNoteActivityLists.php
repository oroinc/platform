<?php

namespace Oro\Bundle\NoteBundle\Migrations\Data\Orm;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ActivityListBundle\Migrations\Data\ORM\AddActivityListsData;

class AddNoteActivityLists extends AddActivityListsData implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\NoteBundle\Migrations\Data\ORM\UpdateNotesWithOrganization'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->addActivityListsForActivityClass(
            $manager,
            'OroNoteBundle:Note',
            'owner',
            'organization'
        );
    }
}
