<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Makes the main organization name equal to the name specified in system config.
 */
class MoveOrganizationFromConfig extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $applicationName = $this->container->get('oro_config.global')->get('oro_ui.application_name');
        if (!$applicationName) {
            return;
        }

        /** @var Organization|null $organization */
        $organization = $manager->getRepository(Organization::class)
            ->findOneBy(['name' => LoadOrganizationAndBusinessUnitData::MAIN_ORGANIZATION]);
        if (!$organization) {
            return;
        }

        $organization->setName($applicationName);
        $manager->flush();
    }
}
