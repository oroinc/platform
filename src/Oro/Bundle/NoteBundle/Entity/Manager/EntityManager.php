<?php

namespace Oro\Bundle\NoteBundle\Entity\Manager;

use Doctrine\ORM\EntityManager as OrmEntityManager;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Entity\Repository\NoteRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class EntityManager
{
    /** @var OrmEntityManager */
    protected $em;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var NameFormatter */
    protected $nameFormatter;

    /** @var CacheManager */
    protected $imageCacheManager;

    /**
     * @param OrmEntityManager $em
     * @param SecurityFacade   $securityFacade
     * @param AclHelper        $aclHelper
     * @param NameFormatter    $nameFormatter
     * @param CacheManager     $imageCacheManager
     */
    public function __construct(
        OrmEntityManager $em,
        SecurityFacade $securityFacade,
        AclHelper $aclHelper,
        NameFormatter $nameFormatter,
        CacheManager $imageCacheManager
    ) {
        $this->em                = $em;
        $this->securityFacade    = $securityFacade;
        $this->aclHelper         = $aclHelper;
        $this->nameFormatter     = $nameFormatter;
        $this->imageCacheManager = $imageCacheManager;
    }

    /**
     * @param string $entityClass
     * @param int    $entityId
     * @param string $sorting
     * @return Note[]
     */
    public function getList($entityClass, $entityId, $sorting)
    {
        /** @var NoteRepository $repo */
        $repo = $this->em->getRepository('OroNoteBundle:Note');
        $qb   = $repo->getAssociatedNotesQueryBuilder($entityClass, $entityId)
            ->orderBy('note.createdAt', $sorting);

        $query = $this->aclHelper->apply($qb);

        return $query->getResult();
    }

    /**
     * @param Note[] $entities
     * @return array
     */
    public function getEntityViewModels($entities)
    {
        $result = [];
        foreach ($entities as $entity) {
            $result[] = $this->getEntityViewModel($entity);
        }
        return $result;
    }

    /**
     * @param Note $entity
     * @return array
     */
    public function getEntityViewModel(Note $entity)
    {
        $result = [
            'id'        => $entity->getId(),
            'message'   => $entity->getMessage(),
            'createdAt' => $entity->getCreatedAt()->format('c'),
            'updatedAt' => $entity->getUpdatedAt()->format('c'),
            'editable'  => $this->securityFacade->isGranted('EDIT', $entity),
            'removable' => $this->securityFacade->isGranted('DELETE', $entity),
        ];
        $this->addUser($result, 'createdBy', $entity->getOwner());
        $this->addUser($result, 'updatedBy', $entity->getUpdatedBy());

        return $result;
    }

    /**
     * @param array  $result
     * @param string $attrName
     * @param User   $user
     */
    protected function addUser(array &$result, $attrName, $user)
    {
        if ($user) {
            $result[$attrName]               = $this->nameFormatter->format($user);
            $result[$attrName . '_id']       = $user->getId();
            $result[$attrName . '_viewable'] = $this->securityFacade->isGranted('VIEW', $user);
            $result[$attrName . '_avatar']   = $user->getImagePath()
                ? $this->imageCacheManager->getBrowserPath($user->getImagePath(), 'avatar_xsmall')
                : null;
        }
    }
}
