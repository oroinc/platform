<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\SecurityContext;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;

class FolderFilterProvider
{
    const EMAIL_FOLDER = 'OroEmailBundle:EmailFolder';

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
        $query = $this->em->getRepository(self::EMAIL_FOLDER)->createQueryBuilder('f')
            ->leftJoin('f.origin', 'o')
            ->where('o.owner = :owner_id')
        ->setParameter('owner_id', $user->getId());
        $folders = $query->getQuery()->getResult();
        $results = [];
        /**
         * @var EmailFolder $folder
         */
        foreach ($folders as $folder) {
            $results[$folder->getId()]= $folder->getFullName();
        }

        return $results;
    }
}
