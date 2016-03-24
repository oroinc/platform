<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Symfony\Component\DependencyInjection\Container;

use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;

abstract class DeleteDataByDeleteHandler implements ProcessorInterface
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
        /** @var Context $context */

        if (!$context->hasResult()) {
            // result deleted or not supported
            return;
        }

        if (!$this->doctrineHelper->isManageableEntityClass($context->getClassName())) {
            // only manageable entities are supported
            return;
        }

        $deleteHandlerServiceId = $context->getConfig()->getDeleteHandler();
        if (!$deleteHandlerServiceId) {
            $deleteHandlerServiceId = self::DEFAULT_DELETE_HANDLER;
        }

        $deleteHandler = $this->container->get($deleteHandlerServiceId);
        if ($deleteHandler instanceof DeleteHandler) {
            $this->processDelete($context, $deleteHandler);
            $context->removeResult();
        }
    }

    /**
     * Deletes context's result by delete handler
     *
     * @param ContextInterface $context
     * @param DeleteHandler    $handler
     */
    abstract protected function processDelete(ContextInterface $context, DeleteHandler $handler);
}
