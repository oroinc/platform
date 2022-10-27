<?php

namespace Oro\Bundle\OrganizationBundle\Entity\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Provides a set of methods to simplify manage of the Organization entity.
 */
class OrganizationManager
{
    /** @var ManagerRegistry */
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Gets organization by its identifier.
     *
     * @param int $id
     *
     * @return Organization
     *
     * @throws NoResultException if the organization was not found
     */
    public function getOrganizationById($id)
    {
        $organization = $this->getRepository()->find($id);
        if (!$organization) {
            throw new NoResultException();
        }

        return $organization;
    }

    /**
     * Gets organization by its name.
     *
     * @param string $name
     *
     * @return Organization
     *
     * @throws NoResultException if the organization was not found
     */
    public function getOrganizationByName($name)
    {
        return $this->getRepository()->getOrganizationByName($name);
    }

    /**
     * @param Organization $organization
     * @param bool         $flush
     */
    public function updateOrganization(Organization $organization, $flush = true)
    {
        $em = $this->getEntityManager();
        $em->persist($organization);
        if ($flush) {
            $em->flush();
        }
    }

    /**
     * @param User   $user
     * @param string $name
     *
     * @return Organization|null
     */
    public function getEnabledUserOrganizationByName(User $user, $name)
    {
        return $this->getRepository()->getEnabledByUserAndName($user, $name, true, true);
    }

    /**
     * @return OrganizationRepository
     */
    protected function getRepository()
    {
        return $this->getEntityManager()->getRepository(Organization::class);
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(Organization::class);
    }
}
