<?php

namespace Oro\Bundle\NoteBundle\Handler;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ActivityBundle\Handler\ActivityEntityDeleteHandlerExtensionInterface;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NoteBundle\Entity\Note;

/**
 * The activity entity associations delete handler extension for Note entity.
 */
class NoteActivityEntityDeleteHandlerExtension implements ActivityEntityDeleteHandlerExtensionInterface
{
    /** @var ActivityEntityDeleteHandlerExtensionInterface */
    private $innerExtension;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var EntityDeleteAccessDeniedExceptionFactory */
    private $accessDeniedExceptionFactory;

    public function __construct(
        ActivityEntityDeleteHandlerExtensionInterface $innerExtension,
        DoctrineHelper $doctrineHelper,
        EntityDeleteAccessDeniedExceptionFactory $accessDeniedExceptionFactory
    ) {
        $this->innerExtension = $innerExtension;
        $this->doctrineHelper = $doctrineHelper;
        $this->accessDeniedExceptionFactory = $accessDeniedExceptionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function assertDeleteGranted($entity, $targetEntity): void
    {
        $this->innerExtension->assertDeleteGranted($entity, $targetEntity);
        if ($entity instanceof Note) {
            $targetEntities = $entity->getActivityTargets();
            if (count($targetEntities) === 1
                && $this->isTargetRequestedForDelete(reset($targetEntities), $targetEntity)
            ) {
                throw $this->accessDeniedExceptionFactory->createAccessDeniedException(sprintf(
                    'the last activity target entity of %s could not be removed',
                    Note::class
                ));
            }
        }
    }

    /**
     * @param object $targetEntity
     * @param object $requestedTargetEntity
     *
     * @return bool
     */
    private function isTargetRequestedForDelete($targetEntity, $requestedTargetEntity): bool
    {
        return
            ClassUtils::getClass($targetEntity) === ClassUtils::getClass($requestedTargetEntity)
            && $this->getEntityId($targetEntity) === $this->getEntityId($requestedTargetEntity);
    }

    /**
     * @param object $entity
     *
     * @return mixed
     */
    private function getEntityId($entity)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }
}
