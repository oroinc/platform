<?php

namespace Oro\Bundle\CommentBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\CommentBundle\Tools\CommentAssociationHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CommentPlaceholderFilter
{
    /** @var CommentAssociationHelper */
    protected $commentAssociationHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /**
     * @param CommentAssociationHelper      $commentAssociationHelper
     * @param DoctrineHelper                $doctrineHelper
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
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
