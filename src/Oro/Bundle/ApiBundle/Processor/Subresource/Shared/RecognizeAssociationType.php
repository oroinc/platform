<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Exception\ActionNotAllowedException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Makes sure that the association name exists in the context.
 * Computes the related entity class name and the relationship type
 * based on the parent class name and the association name
 * and sets them into the "class" and the "collection" attributes of the context.
 */
class RecognizeAssociationType implements ProcessorInterface
{
    /** @var SubresourcesProvider */
    private $subresourcesProvider;

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
        if ($associationName) {
            $this->setAssociationType($context, $associationName);
        } else {
            $context->addError(
                Error::createValidationError(
                    Constraint::RELATIONSHIP,
                    'The association name must be set in the context.'
                )
            );
        }
    }

    /**
     * @param SubresourceContext $context
     * @param string             $associationName
     */
    private function setAssociationType(SubresourceContext $context, string $associationName): void
    {
        $subresource = $this->getSubresource($context, $associationName);
        if (null === $subresource) {
            $context->addError(
                Error::createValidationError(
                    Constraint::RELATIONSHIP,
                    'Unsupported subresource.',
                    Response::HTTP_NOT_FOUND
                )
            );

            return;
        }

        if ($subresource->isExcludedAction($context->getAction())) {
            throw new ActionNotAllowedException();
        }

        $targetClassName = $subresource->getTargetClassName();
        if (!$targetClassName) {
            $context->addError(
                Error::createValidationError(
                    Constraint::RELATIONSHIP,
                    'The target entity type cannot be recognized.'
                )
            );

            return;
        }

        $context->setClassName($targetClassName);
        $context->setIsCollection($subresource->isCollection());
    }

    /**
     * @param SubresourceContext $context
     * @param string             $associationName
     *
     * @return ApiSubresource|null
     */
    private function getSubresource(SubresourceContext $context, string $associationName): ?ApiSubresource
    {
        $entitySubresources = $this->subresourcesProvider->getSubresources(
            $context->getParentClassName(),
            $context->getVersion(),
            $context->getRequestType()
        );

        if (null === $entitySubresources) {
            return null;
        }

        return $entitySubresources->getSubresource($associationName);
    }
}
