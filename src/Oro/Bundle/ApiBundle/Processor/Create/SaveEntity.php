<?php

namespace Oro\Bundle\ApiBundle\Processor\Create;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Saves new ORM entity to the database and save its identifier into the Context.
 */
class SaveEntity implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        $entity = $context->getResult();
        if (!is_object($entity)) {
            // entity does not exist
            return;
        }

        $em = $this->doctrineHelper->getEntityManager($entity, false);
        if (!$em) {
            // only manageable entities are supported
            return;
        }

        $em->persist($entity);
        try {
            $em->flush();
        } catch (UniqueConstraintViolationException $e) {
            $context->addError(
                Error::createValidationError(Constraint::CONFLICT, 'The entity already exists')
                    ->setStatusCode(Response::HTTP_CONFLICT)
                    ->setInnerException($e)
            );
        }

        // save entity id into the Context
        if (!$context->hasErrors()) {
            $id = $context->getMetadata()->getIdentifierValue($entity);
            if (null !== $id) {
                $context->setId($id);
            }
        }
    }
}
