<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Bundle\ApiBundle\Util\EntityLoader;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Creates new instance of the entity.
 * If the entity type does not have id generator and an entity
 * with the specified identifier already exists then a validation error
 * is added to the context.
 */
class CreateEntity implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityLoader */
    protected $entityLoader;

    /** @var EntityInstantiator */
    protected $entityInstantiator;

    /**
     * @param DoctrineHelper     $doctrineHelper
     * @param EntityLoader       $entityLoader
     * @param EntityInstantiator $entityInstantiator
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityLoader $entityLoader,
        EntityInstantiator $entityInstantiator
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityLoader = $entityLoader;
        $this->entityInstantiator = $entityInstantiator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        if ($context->hasResult()) {
            // the entity already exists
            return;
        }

        $entityClass = $context->getClassName();
        $entityId = $context->getId();
        if ($entityId && $this->doctrineHelper->isManageableEntityClass($entityClass)) {
            $metadata = $context->getMetadata();
            if (!$metadata->hasIdentifierGenerator()
                && null !== $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
            ) {
                $context->addError(
                    Error::createValidationError(Constraint::CONFLICT, 'The entity already exists')
                        ->setStatusCode(Response::HTTP_CONFLICT)
                );
            }
        }
        if (!$context->hasErrors()) {
            $context->setResult($this->entityInstantiator->instantiate($entityClass));
        }
    }
}
