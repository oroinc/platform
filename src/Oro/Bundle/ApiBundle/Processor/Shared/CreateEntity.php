<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\HttpFoundation\Response;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;

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

    /** @var EntityInstantiator */
    protected $entityInstantiator;

    /**
     * @param DoctrineHelper     $doctrineHelper
     * @param EntityInstantiator $entityInstantiator
     */
    public function __construct(DoctrineHelper $doctrineHelper, EntityInstantiator $entityInstantiator)
    {
        $this->doctrineHelper = $doctrineHelper;
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
            if (!$metadata->hasIdentifierGenerator() && $this->isEntityExist($entityClass, $entityId)) {
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

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return bool
     */
    private function isEntityExist($entityClass, $entityId)
    {
        $existingEntity = $this->doctrineHelper->getEntityManagerForClass($entityClass)
            ->find($entityClass, $entityId);

        return null !== $existingEntity;
    }
}
