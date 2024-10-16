<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;

/**
 * Sets a default organization to Attachment entity.
 */
class UpdateAttachmentsWithOrganization extends UpdateWithOrganization implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrganizationAndBusinessUnitData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->update($manager, Attachment::class);
    }
}
