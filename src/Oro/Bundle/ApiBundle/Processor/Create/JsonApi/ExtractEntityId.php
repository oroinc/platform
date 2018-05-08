<?php

namespace Oro\Bundle\ApiBundle\Processor\Create\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks whether entity identifier exists in the request data,
 * and if so, adds it to the context.
 * If the entity identifier does not exist in the request data
 * and the entity type does not have id generator then a validation error
 * is added to the context.
 */
class ExtractEntityId implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext|SingleItemContext $context */

        if (null !== $context->getId()) {
            // an entity id is already set
            return;
        }

        $requestData = $context->getRequestData();
        if (!array_key_exists(JsonApiDoc::DATA, $requestData)) {
            // unsupported request data or the data are already normalized
            return;
        }

        $data = $requestData[JsonApiDoc::DATA];
        if (array_key_exists(JsonApiDoc::ID, $data)) {
            $context->setId($data[JsonApiDoc::ID]);
        } else {
            $metadata = $context->getMetadata();
            if (null !== $metadata && !$metadata->hasIdentifierGenerator()) {
                $context->addError($this->createRequiredEntityIdError());
            }
        }
    }

    /**
     * @return Error
     */
    private function createRequiredEntityIdError()
    {
        return Error::createValidationError(Constraint::ENTITY_ID, 'The identifier is mandatory')
            ->setSource(ErrorSource::createByPropertyPath('id'));
    }
}
