<?php

namespace Oro\Bundle\ActionBundle\Handler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler as BaseDeleteHandler;

class DeleteHandler
{
    /** @var BaseDeleteHandler */
    protected $deleteHandler;

    /** @var ApiEntityManager */
    protected $apiEntityManager;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param BaseDeleteHandler $deleteHandler
     * @param ApiEntityManager $apiEntityManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        BaseDeleteHandler $deleteHandler,
        ApiEntityManager $apiEntityManager,
        DoctrineHelper $doctrineHelper
    ) {
        $this->deleteHandler = $deleteHandler;
        $this->apiEntityManager = $apiEntityManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param object $entity
     */
    public function handleDelete($entity)
    {
        $this->apiEntityManager->setClass($this->doctrineHelper->getEntityClass($entity));

        $id = $this->doctrineHelper->getEntityIdentifier($entity);

        $this->deleteHandler->handleDelete($id, $this->apiEntityManager);
    }
}
