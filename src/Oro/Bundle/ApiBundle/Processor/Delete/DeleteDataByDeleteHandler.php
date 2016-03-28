<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Symfony\Component\DependencyInjection\Container;

use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;

/**
 * Deletes object by DeleteProcessHandler.
 */
class DeleteDataByDeleteHandler implements ProcessorInterface
{
    const DEFAULT_DELETE_HANDLER = 'oro_soap.handler.delete';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var Container */
    protected $container;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param Container      $container
     */
    public function __construct(DoctrineHelper $doctrineHelper, Container $container)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var DeleteContext $context */

        $entity = $context->getResult();
        if (!is_object($entity)) {
            // entity already deleted or not supported
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $deleteHandlerServiceId = $context->getConfig()->getDeleteHandler();
        if (!$deleteHandlerServiceId) {
            $deleteHandlerServiceId = self::DEFAULT_DELETE_HANDLER;
        }

        $deleteHandler = $this->container->get($deleteHandlerServiceId);
        if ($deleteHandler instanceof DeleteHandler) {
            $deleteHandler->processDelete($entity, $this->doctrineHelper->getEntityManagerForClass($entityClass));
            $context->removeResult();
        }
    }
}
