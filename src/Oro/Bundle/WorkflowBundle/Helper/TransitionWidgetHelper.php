<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Helper for managing workflow transition widget entity references.
 *
 * This helper provides methods to retrieve or create entity references needed for
 * rendering and processing workflow transition widgets.
 */
class TransitionWidgetHelper
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Try to get reference to entity
     *
     * @param string $entityClass
     * @param mixed $entityId
     *
     * @throws BadRequestHttpException
     * @return mixed
     */
    public function getOrCreateEntityReference($entityClass, $entityId = null)
    {
        try {
            if ($entityId) {
                $entity = $this->doctrineHelper->getEntityReference($entityClass, $entityId);
            } else {
                $entity = $this->doctrineHelper->createEntityInstance($entityClass);
            }
        } catch (NotManageableEntityException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        return $entity;
    }
}
