<?php

namespace Oro\Bundle\OrganizationBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationManager
{
    /**
     * @var EntityManager
     */
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
}
