<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\Constraint;

/**
 * Makes sure that the association name exists in the Context.
 * Computes the related entity class name and the relationship type
 * based on the parent class name and the association name
 * and sets them into the "class" and the "collection" attributes of the Context.
 */
class RecognizeAssociationType implements ProcessorInterface
{
    /** @var SubresourcesProvider */
    protected $subresourcesProvider;

    /**
     * @param SubresourcesProvider $subresourcesProvider
     */
    public function __construct(SubresourcesProvider $subresourcesProvider)
    {
        $this->subresourcesProvider = $subresourcesProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        $entityClass = $context->getClassName();
        if ($entityClass) {
            // the entity class is already set
            return;
        }

        $associationName = $context->getAssociationName();
        if (!$associationName) {
            $context->addError(
                Error::createValidationError(
                    Constraint::RELATIONSHIP,
                    'The association name must be set in the context.'
                )
            );

            return;
        }

        if (!$this->setAssociationType($context, $associationName)) {
            $context->addError(
                Error::createValidationError(
                    Constraint::RELATIONSHIP,
                    'The target entity type cannot be recognized.'
                )
            );
        }
    }

    /**
     * @param SubresourceContext $context
     * @param string             $associationName
     *
     * @return bool
     */
    protected function setAssociationType(SubresourceContext $context, $associationName)
    {
        $entitySubresources = $this->subresourcesProvider->getSubresources(
            $context->getParentClassName(),
            $context->getVersion(),
            $context->getRequestType()
        );
        if (null === $entitySubresources) {
            return false;
        }
        $subresource = $entitySubresources->getSubresource($associationName);
        if (null === $subresource) {
            return false;
        }
        $targetClassName = $subresource->getTargetClassName();
        if (!$targetClassName) {
            return false;
        }

        $context->setClassName($targetClassName);
        $context->setIsCollection($subresource->isCollection());

        return true;
    }
}
