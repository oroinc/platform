<?php

namespace Oro\Bundle\NoteBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Entity\Repository\NoteRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class NoteManager
{
    /** @var EntityManager */
    protected $em;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var NameFormatter */
    protected $nameFormatter;

    /** @var AttachmentManager */
    protected $attachmentManager;

    /**
     * @param EntityManager  $em
     * @param SecurityFacade $securityFacade
     * @param AclHelper      $aclHelper
     * @param NameFormatter  $nameFormatter
     * @param AttachmentManager $attachmentManager
     */
    public function __construct(
        EntityManager $em,
        SecurityFacade $securityFacade,
        AclHelper $aclHelper,
        NameFormatter $nameFormatter,
        AttachmentManager $attachmentManager
    ) {
        $this->em                = $em;
        $this->securityFacade    = $securityFacade;
        $this->aclHelper         = $aclHelper;
        $this->nameFormatter     = $nameFormatter;
        $this->attachmentManager = $attachmentManager;
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
            'hasUpdate' => $entity->getCreatedAt() != $entity->getUpdatedAt(),
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
            $result[$attrName . '_avatar']   = $user->getAvatar()
                ? $this->attachmentManager->getFilteredImageUrl($user->getAvatar(), 'avatar_xsmall')
                : null;
        }
    }
}
