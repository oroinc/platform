<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * Deletes object by DeleteProcessHandler.
 */
class DeleteDataByDeleteHandler implements ProcessorInterface
{
    /** @var DeleteHandler */
    protected $deleteHandler;

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

        if (!$context->hasResult()) {
            // entity already deleted
            return;
        }

        $object = $context->getResult();

        if (!is_object($object)) {
            // entity already deleted or not supported
            return;
        }

        /** @var ActionsConfig $actions */
        $actions = $context->getConfigOf('actions');
        $deleteAction = $actions->getAction('delete');
        $deleteServiceName = array_key_exists('delete_handler', $deleteAction)
            ? $deleteAction['delete_handler']
            : 'oro_soap.handler.delete';

        $deleteHandler = $this->container->get($deleteServiceName);
        $deleteHandler->processDelete($object, $this->doctrineHelper->getEntityManager($object));
        $context->removeResult();
    }
}
