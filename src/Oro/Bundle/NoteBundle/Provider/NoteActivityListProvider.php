<?php
declare(strict_types=1);

namespace Oro\Bundle\NoteBundle\Provider;

use Oro\Bundle\ActivityBundle\Tools\ActivityAssociationHelper;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Model\ActivityListUpdatedByProviderInterface;
use Oro\Bundle\CommentBundle\Model\CommentProviderInterface;
use Oro\Bundle\CommentBundle\Tools\CommentAssociationHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Provides a way to use Note entity in an activity list.
 */
class NoteActivityListProvider implements
    ActivityListProviderInterface,
    CommentProviderInterface,
    ActivityListDateProviderInterface,
    ActivityListUpdatedByProviderInterface
{
    protected DoctrineHelper $doctrineHelper;
    protected ServiceLink $entityOwnerAccessorLink;
    protected ActivityAssociationHelper $activityAssociationHelper;
    protected CommentAssociationHelper $commentAssociationHelper;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ServiceLink $entityOwnerAccessorLink,
        ActivityAssociationHelper $activityAssociationHelper,
        CommentAssociationHelper $commentAssociationHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityOwnerAccessorLink = $entityOwnerAccessorLink;
        $this->activityAssociationHelper = $activityAssociationHelper;
        $this->commentAssociationHelper = $commentAssociationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicableTarget($entityClass, $accessible = true)
    {
        return $this->activityAssociationHelper->isActivityAssociationEnabled(
            $entityClass,
            Note::class,
            $accessible
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes($entity): array
    {
        return [
            'itemView'   => 'oro_note_widget_info',
            'itemEdit'   => 'oro_note_update',
            'itemDelete' => 'oro_api_delete_note'
        ];
    }

    /**
     * {@inheritdoc}
     * @param Note $entity
     */
    public function getSubject($entity): string
    {
        return $this->truncate(\strip_tags((string)$entity->getMessage()), 100);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription($entity): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     * @param Note $entity
     */
    public function getOwner($entity): ?User
    {
        return $entity->getOwner();
    }

    /**
     * {@inheritdoc}
     * @param Note $entity
     */
    public function getUpdatedBy($entity): ?User
    {
        return $entity->getUpdatedBy();
    }

    /**
     * {@inheritdoc}
     * @param Note $entity
     */
    public function getCreatedAt($entity): ?\DateTime
    {
        return $entity->getCreatedAt();
    }

    /**
     * {@inheritdoc}
     * @param Note $entity
     */
    public function getUpdatedAt($entity): ?\DateTime
    {
        return $entity->getUpdatedAt();
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ActivityList $activityList): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     * @param Note $entity
     */
    public function getOrganization($entity): ?Organization
    {
        return $entity->getOrganization();
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate(): string
    {
        return '@OroNote/Note/js/activityItemTemplate.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityId($entity)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($entity): bool
    {
        if (\is_object($entity)) {
            return $entity instanceof Note;
        }

        return $entity === Note::class;
    }

    /**
     * {@inheritdoc}
     * @param Note $entity
     */
    public function getTargetEntities($entity): array
    {
        return $entity->getActivityTargets();
    }

    /**
     * {@inheritdoc}
     */
    public function isCommentsEnabled($entityClass): bool
    {
        return $this->commentAssociationHelper->isCommentAssociationEnabled($entityClass);
    }

    /**
     * {@inheritdoc}
     * @param Note $entity
     */
    public function getActivityOwners($entity, ActivityList $activityList): array
    {
        $organization = $this->getOrganization($entity);
        $owner = $this->entityOwnerAccessorLink->getService()->getOwner($entity);

        if (!$organization || !$owner) {
            return [];
        }

        $activityOwner = new ActivityOwner();
        $activityOwner->setActivity($activityList);
        $activityOwner->setOrganization($organization);
        $activityOwner->setUser($owner);

        return [$activityOwner];
    }

    /**
     * {@inheritDoc}
     */
    public function isActivityListApplicable(ActivityList $activityList): bool
    {
        return true;
    }

    protected function truncate(string $string, int $length, string $etc = '...'): string
    {
        if (\mb_strlen($string) <= $length) {
            return $string;
        }

        $length -= \min($length, \mb_strlen($etc));

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $string = \preg_replace('/\s+?(\S+)?$/u', '', \mb_substr($string, 0, $length + 1));

        return \mb_substr($string, 0, $length) . $etc;
    }
}
