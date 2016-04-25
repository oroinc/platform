<?php

namespace Oro\Bundle\CommentBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\CommentBundle\Tools\CommentAssociationHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class CommentPlaceholderFilter
{
    /** @var CommentAssociationHelper */
    protected $commentAssociationHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param CommentAssociationHelper $commentAssociationHelper
     * @param DoctrineHelper           $doctrineHelper
     * @param SecurityFacade           $securityFacade
     */
    public function __construct(
        CommentAssociationHelper $commentAssociationHelper,
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade
    ) {
        $this->commentAssociationHelper = $commentAssociationHelper;
        $this->doctrineHelper           = $doctrineHelper;
        $this->securityFacade           = $securityFacade;
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
            || !$this->securityFacade->isGranted('oro_comment_view')
        ) {
            return false;
        }

        return $this->commentAssociationHelper->isCommentAssociationEnabled(ClassUtils::getClass($entity));
    }
}
