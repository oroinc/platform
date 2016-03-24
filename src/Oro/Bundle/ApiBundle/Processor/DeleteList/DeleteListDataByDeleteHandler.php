<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Symfony\Component\DependencyInjection\Container;

use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;

class DeleteListDataByDeleteHandler implements ProcessorInterface
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
        /** @var DeleteListContext $context */

        $entityList = $context->getResult();
        if (empty($entityList)) {
            // list already deleted or not supported
            return;
        }

        $deleteHandlerServiceId = $context->getConfig()->getDeleteHandler();
        if (!$deleteHandlerServiceId) {
            $deleteHandlerServiceId = self::DEFAULT_DELETE_HANDLER;
        }

        $deleteHandler = $this->container->get($deleteHandlerServiceId);
        $entityManager = $this->doctrineHelper->getEntityManagerForClass($context->getClassName());
        if ($deleteHandler instanceof DeleteHandler) {
            $entityManager->getConnection()->beginTransaction();
            try {
                foreach ($entityList as $entity) {
                    $deleteHandler->processDelete($entity, $entityManager);
                }
                $entityManager->getConnection()->commit();
            } catch (\Exception $e) {
                $entityManager->getConnection()->rollBack();
            }

            $context->removeResult();
        }
    }
}