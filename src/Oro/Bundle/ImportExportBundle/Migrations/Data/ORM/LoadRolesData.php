<?php

declare(strict_types=1);

namespace Oro\Bundle\ImportExportBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractLoadAclData;

/**
 * Sets the default permissions on import/export results for the default roles of a new application.
 */
class LoadRolesData extends AbstractLoadAclData
{
    #[\Override]
    protected function getDataPath(): string
    {
        return '@OroImportExportBundle/Migrations/Data/ORM/Roles/roles.yml';
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        if ($this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        parent::load($manager);
    }
}
