<?php

namespace Oro\Bundle\NoteBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Entity\Repository\NoteRepository;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * General methods of working with notes
 */
class NoteManager
{
    private EntityManager $em;

    private AuthorizationCheckerInterface $authorizationChecker;

    private AclHelper $aclHelper;

    private EntityNameResolver $entityNameResolver;

    private AttachmentProvider $attachmentProvider;

    private PictureSourcesProviderInterface $pictureSourcesProvider;

    public function __construct(
        EntityManager $em,
        AuthorizationCheckerInterface $authorizationChecker,
        AclHelper $aclHelper,
        EntityNameResolver $entityNameResolver,
        AttachmentProvider $attachmentProvider,
        PictureSourcesProviderInterface $pictureSourcesProvider
    ) {
        $this->em = $em;
        $this->authorizationChecker = $authorizationChecker;
        $this->aclHelper = $aclHelper;
        $this->entityNameResolver = $entityNameResolver;
        $this->attachmentProvider = $attachmentProvider;
        $this->pictureSourcesProvider = $pictureSourcesProvider;
    }

    /**
     * @param string $entityClass
     * @param int    $entityId
     * @param string $sorting
     * @return Note[]
     */
    public function getList(string $entityClass, int $entityId, string $sorting)
    {
        /** @var NoteRepository $repo */
        $repo = $this->em->getRepository('OroNoteBundle:Note');
        $qb   = $repo->getAssociatedNotesQueryBuilder($entityClass, $entityId)
            ->orderBy('note.createdAt', QueryBuilderUtil::getSortOrder($sorting));

        $query = $this->aclHelper->apply($qb, BasicPermission::VIEW, [AclHelper::CHECK_RELATIONS => false]);

        return $query->getResult();
    }

    /**
     * @param Note[] $entities
     * @return array
     */
    public function getEntityViewModels(array $entities): array
    {
        $result = [];
        foreach ($entities as $entity) {
            $result[] = $this->getEntityViewModel($entity);
        }

        return $result;
    }

    public function getEntityViewModel(Note $entity): array
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

        return array_merge($result, $this->attachmentProvider->getAttachmentInfo($entity));
    }

    /**
     * @param array  $result
     * @param string $attrName
     * @param User   $user
     */
    protected function addUser(array &$result, $attrName, $user)
    {
        if ($user) {
            $result[$attrName] = $this->entityNameResolver->getName($user);
            $result[$attrName . '_id'] = $user->getId();
            $result[$attrName . '_viewable'] = $this->authorizationChecker->isGranted('VIEW', $user);
            $result[$attrName . '_avatarPicture'] = $this->pictureSourcesProvider
                ->getFilteredPictureSources($user->getAvatar(), 'avatar_xsmall');
        }
    }
}
