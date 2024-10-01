<?php

namespace Oro\Bundle\NoteBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;

/**
 * Sets a default organization to Note entity.
 */
class UpdateNotesWithOrganization extends UpdateWithOrganization implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrganizationAndBusinessUnitData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->update($manager, Note::class, 'organization', true);
    }
}
