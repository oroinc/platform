<?php

namespace Oro\Bundle\CommentBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\CommentBundle\Tools\CommentAssociationHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Filters and determines whether a comment placeholder should be displayed for a given entity.
 *
 * This class checks multiple conditions to determine if comments can be shown for an entity:
 * - Verifies the entity is a valid Doctrine-managed object
 * - Checks if the current user has permission to view comments
 * - Confirms that comment associations are enabled for the entity class
 *
 * This is typically used in placeholder rendering to conditionally display comment sections
 * in the UI based on entity type, user permissions, and system configuration.
 */
class CommentPlaceholderFilter
{
    /** @var CommentAssociationHelper */
    protected $commentAssociationHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    public function __construct(
        CommentAssociationHelper $commentAssociationHelper,
        DoctrineHelper $doctrineHelper,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->commentAssociationHelper = $commentAssociationHelper;
        $this->doctrineHelper = $doctrineHelper;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Checks if the entity can have comments
     *
     * @param object|null $entity
     *
     * @return bool
     */
    public function isApplicable($entity)
    {
        if (!is_object($entity)
            || !$this->doctrineHelper->isManageableEntity($entity)
            || !$this->authorizationChecker->isGranted('oro_comment_view')
        ) {
            return false;
        }

        return $this->commentAssociationHelper->isCommentAssociationEnabled(ClassUtils::getClass($entity));
    }
}
