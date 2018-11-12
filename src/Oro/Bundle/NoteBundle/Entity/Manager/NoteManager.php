<?php

namespace Oro\Bundle\NoteBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Entity\Repository\NoteRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * General methods of working with notes
 */
class NoteManager
{
    /** @var EntityManager */
    protected $em;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var AttachmentProvider */
    protected $attachmentProvider;

    /** @var AttachmentManager */
    protected $attachmentManager;

    /**
     * @param EntityManager                 $em
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param AclHelper                     $aclHelper
     * @param EntityNameResolver            $entityNameResolver
     * @param AttachmentProvider            $attachmentProvider
     * @param AttachmentManager             $attachmentManager
     */
    public function __construct(
        EntityManager $em,
        AuthorizationCheckerInterface $authorizationChecker,
        AclHelper $aclHelper,
        EntityNameResolver $entityNameResolver,
        AttachmentProvider $attachmentProvider,
        AttachmentManager $attachmentManager
    ) {
        $this->em = $em;
        $this->authorizationChecker = $authorizationChecker;
        $this->aclHelper = $aclHelper;
        $this->entityNameResolver = $entityNameResolver;
        $this->attachmentProvider = $attachmentProvider;
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
            ->orderBy('note.createdAt', QueryBuilderUtil::getSortOrder($sorting));

        $query = $this->aclHelper->apply($qb, 'VIEW', [AclHelper::CHECK_RELATIONS => false]);

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
            'editable'  => $this->authorizationChecker->isGranted('EDIT', $entity),
            'removable' => $this->authorizationChecker->isGranted('DELETE', $entity),
        ];
        $this->addUser($result, 'createdBy', $entity->getOwner());
        $this->addUser($result, 'updatedBy', $entity->getUpdatedBy());
        $result = array_merge($result, $this->attachmentProvider->getAttachmentInfo($entity));

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
            $result[$attrName . '_viewable'] = $this->authorizationChecker->isGranted('VIEW', $user);
            $avatar                          = $user->getAvatar();
            $result[$attrName . '_avatar']   = $avatar
                ? $this->attachmentManager->getFilteredImageUrl($avatar, 'avatar_xsmall')
                : null;
        }
    }
}
