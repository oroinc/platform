<?php

namespace Oro\Bundle\EmbeddedFormBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\MigrationBundle\Fixture\RenamedFixtureInterface;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;

/**
 * Sets a default organization to EmbeddedForm entity.
 */
class UpdateEmbeddedFormWithOrganization extends UpdateWithOrganization implements
    DependentFixtureInterface,
    RenamedFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrganizationAndBusinessUnitData::class];
    }

    #[\Override]
    public function getPreviousClassNames(): array
    {
        return [
            'Oro\\Bundle\\EmbeddedForm\\Migrations\\Data\\ORM\\UpdateEmbeddedFormWithOrganization',
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->update($manager, EmbeddedForm::class, 'owner');
    }
}
