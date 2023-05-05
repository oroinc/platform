<?php

namespace Oro\Bundle\ApiBundle\Processor\Create;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Saves new ORM entity to the database and save its identifier into the context.
 */
class SaveEntity implements ProcessorInterface
{
    public const OPERATION_NAME = 'save_new_entity';

    private DoctrineHelper $doctrineHelper;
    private FlushDataHandlerInterface $flushDataHandler;

    public function __construct(DoctrineHelper $doctrineHelper, FlushDataHandlerInterface $flushDataHandler)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->flushDataHandler = $flushDataHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CreateContext $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // the entity was already saved
            return;
        }

        $entity = $context->getResult();
        if (!\is_object($entity)) {
            // entity does not exist
            return;
        }

        $em = $this->doctrineHelper->getEntityManager($entity, false);
        if (null === $em) {
            // only manageable entities are supported
            return;
        }

        $metadata = $context->getMetadata();
        if (null === $metadata) {
            // the metadata does not exist
            return;
        }

        try {
            $this->flushDataHandler->flushData(
                $em,
                new FlushDataHandlerContext([$context], $context->getSharedData())
            );
        } catch (UniqueConstraintViolationException $e) {
            $context->addError(
                Error::createConflictValidationError('The entity already exists')
                    ->setInnerException($e)
            );
        }

        // save entity id into the context
        if (!$context->hasErrors()) {
            $id = $metadata->getIdentifierValue($entity);
            if (null !== $id) {
                $context->setId($id);
            }
        }

        $context->setProcessed(self::OPERATION_NAME);
    }
}
