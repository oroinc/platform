<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\SecurityContext;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

class OriginFolderFilterProvider
{
    const EMAIL_ORIGIN = 'OroEmailBundle:EmailOrigin';

    /**
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @var OroEntityManager
     */
    protected $em;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(
        OroEntityManager $em,
        SecurityContext $securityContext
    ) {
        $this->em = $em;
        $this->securityContext = $securityContext;
    }

    /**
     * Get marketing list types choices.
     *
     * @return array
     */
    public function getListTypeChoices()
    {
        $user = $this->securityContext->getToken()->getUser();
        $origins = $this->em->getRepository(self::EMAIL_ORIGIN)->findBy(['owner'=>$user->getId()]);
        $results = [];
        /**
         * @var EmailOrigin $origin
         */
        foreach ($origins as $origin) {
            $folders = $origin->getFolders();

            if (count($folders)>0) {
                $results[$origin->getUserLogin()]= [];
                foreach ($folders as $folder) {
                    $results[$origin->getUserLogin()][$folder->getId()] = $folder->getFullName();
                }
            }
        }

        return $results;
    }
}
