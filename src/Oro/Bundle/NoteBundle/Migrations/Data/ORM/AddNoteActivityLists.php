<?php

namespace Oro\Bundle\NoteBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityListBundle\Migrations\Data\ORM\AddActivityListsData;
use Oro\Bundle\NoteBundle\Entity\Note;

/**
 * Adds activity lists for Note entity.
 */
class AddNoteActivityLists extends AddActivityListsData implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [UpdateNotesWithOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->addActivityListsForActivityClass(
            $manager,
            Note::class,
            'owner',
            'organization'
        );
    }
}
