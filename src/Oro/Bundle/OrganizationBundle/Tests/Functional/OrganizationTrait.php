<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Functional;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @method ContainerInterface getContainer()
 */
trait OrganizationTrait
{
    public function getOrganization(): Organization
    {
        $organizationRepository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(Organization::class)
            ->getRepository(Organization::class);

        return $organizationRepository->getFirst();
    }
}
