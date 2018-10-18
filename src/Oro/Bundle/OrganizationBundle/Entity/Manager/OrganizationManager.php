<?php

namespace Oro\Bundle\OrganizationBundle\Entity\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
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

    /**
     * @param ManagerRegistry $doctrine
     */
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
        $storageManager = $this->getStorageManager();
        $storageManager->persist($organization);
        if ($flush) {
            $storageManager->flush();
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
     * @return EntityManager
     */
    public function getStorageManager()
    {
        return $this->doctrine->getManagerForClass(Organization::class);
    }

    /**
     * @return OrganizationRepository
     */
    public function getRepository()
    {
        return $this->getStorageManager()->getRepository(Organization::class);
    }
}
