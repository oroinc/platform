<?php

namespace Oro\Bundle\CommentBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class CommentPlaceholderFilter
{
    /** @var ConfigProvider */
    protected $commentConfigProvider;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param ConfigProvider $commentConfigProvider
     * @param ConfigProvider $entityConfigProvider
     * @param DoctrineHelper $doctrineHelper
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        ConfigProvider $commentConfigProvider,
        ConfigProvider $entityConfigProvider,
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade
    ) {
        $this->commentConfigProvider = $commentConfigProvider;
        $this->entityConfigProvider  = $entityConfigProvider;
        $this->doctrineHelper        = $doctrineHelper;
        $this->securityFacade        = $securityFacade;
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

        $className = ClassUtils::getClass($entity);

        return
            $this->commentConfigProvider->hasConfig($className)
            && $this->commentConfigProvider->getConfig($className)->is('enabled')
            && $this->entityConfigProvider->hasConfig(
                Comment::ENTITY_NAME,
                ExtendHelper::buildAssociationName($className)
            );
    }
}
