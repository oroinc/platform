<?php

namespace Oro\Bundle\OrganizationBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\UserBundle\Entity\User;

class OrganizationManager
{
    /** @var EntityManager */
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param int $id
     * @return Organization
     */
    public function getOrganizationById($id)
    {
        return $this->em->getRepository('OroOrganizationBundle:Organization')->getOrganizationById($id);
    }

    /**
     * @param string $name
     * @return Organization
     */
    public function getOrganizationByName($name)
    {
        return $this->getOrganizationRepo()->getOrganizationByName($name);
    }

    /**
     * @return OrganizationRepository
     */
    public function getOrganizationRepo()
    {
        return $this->em->getRepository('OroOrganizationBundle:Organization');
    }

    /**
     * @param Organization $organization
     * @param bool         $flush
     */
    public function updateOrganization(Organization $organization, $flush = true)
    {
        $this->em->persist($organization);
        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * @param User   $user
     * @param string $name
     *
     * @return null|Organization
     */
    public function getEnabledUserOrganizationByName(User $user, $name)
    {
        return $this->getOrganizationRepo()->getEnabledByUserAndName($user, $name, true, true);
    }
}
