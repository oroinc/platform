<?php

namespace Oro\Bundle\NoteBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Entity\Repository\NoteRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\UserBundle\Entity\User;

class NoteManager
{
    /** @var EntityManager */
    protected $em;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var AttachmentManager */
    protected $attachmentManager;

    /**
     * @param EntityManager      $em
     * @param SecurityFacade     $securityFacade
     * @param AclHelper          $aclHelper
     * @param EntityNameResolver $entityNameResolver
     * @param AttachmentManager  $attachmentManager
     */
    public function __construct(
        EntityManager $em,
        SecurityFacade $securityFacade,
        AclHelper $aclHelper,
        EntityNameResolver $entityNameResolver,
        AttachmentManager $attachmentManager
    ) {
        $this->em                 = $em;
        $this->securityFacade     = $securityFacade;
        $this->aclHelper          = $aclHelper;
        $this->entityNameResolver = $entityNameResolver;
        $this->attachmentManager  = $attachmentManager;
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

        $query = $this->aclHelper->apply($qb, 'VIEW', false);

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
        $result = array_merge($result, $this->getAttachmentInfo($entity));

        return $result;
    }

    /**
     * @param Note $entity
     * @param File    $attachment
     *
     * @return string
     */
    protected function getAttachmentURL($entity, $attachment)
    {
        return $this->attachmentManager->getFileUrl($entity, 'attachment', $attachment, 'download', true);
    }

    /**
     * @param $entity
     *
     * @return File
     */
    protected function getAttachment($entity)
    {
        $accessor   = PropertyAccess::createPropertyAccessor();
        $attachment = $accessor->getValue($entity, 'attachment');

        return $attachment;
    }

    /**
     * @param Note $entity
     *
     * @return array
     */
    public function getAttachmentInfo(Note $entity)
    {
        $result     = [];
        $attachment = $this->getAttachment($entity);
        if ($attachment) {
            $result = [
                'attachmentURL'      => $this->getAttachmentURL($entity, $attachment),
                'attachmentSize'     => $this->attachmentManager->getFileSize($attachment->getFileSize()),
                'attachmentFileName' => $attachment->getOriginalFilename(),
            ];
        }
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
            $result[$attrName]               = $this->entityNameResolver->getName($user);
            $result[$attrName . '_id']       = $user->getId();
            $result[$attrName . '_viewable'] = $this->securityFacade->isGranted('VIEW', $user);
            $avatar                          = $user->getAvatar();
            $result[$attrName . '_avatar']   = $avatar
                ? $this->attachmentManager->getFilteredImageUrl($avatar, 'avatar_xsmall')
                : null;
        }
    }
}
