<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Collection\KeyObjectCollection;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Loads data from "included" section of the request data to the Context.
 */
class SetIncludedObjects implements ProcessorInterface
{
    /** @var EntityInstantiator */
    protected $entityInstantiator;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param EntityInstantiator $entityInstantiator
     * @param ValueNormalizer    $valueNormalizer
     */
    public function __construct(
        EntityInstantiator $entityInstantiator,
        ValueNormalizer $valueNormalizer
    ) {
        $this->entityInstantiator = $entityInstantiator;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $requestData = $context->getRequestData();
        if (empty($requestData[JsonApiDoc::INCLUDED])) {
            // no included objects
            return;
        }

        $requestType = $context->getRequestType();
        $includedObjects = new KeyObjectCollection();
        foreach ($requestData[JsonApiDoc::INCLUDED] as $key => $item) {
            $className = ValueNormalizerUtil::convertToEntityClass(
                $this->valueNormalizer,
                $item[JsonApiDoc::TYPE],
                $requestType,
                false
            );
            if ($className) {
                $includedObjects->add(
                    $item[JsonApiDoc::ID],
                    $this->entityInstantiator->instantiate($className)
                );
            } else {
                $error = Error::createValidationError(
                    Constraint::ENTITY_TYPE,
                    sprintf('Unknown entity type: %s.', $item[JsonApiDoc::TYPE])
                );
                $error->setSource(
                    ErrorSource::createByPointer(
                        sprintf('/%s/%s/%s', JsonApiDoc::INCLUDED, $key, JsonApiDoc::TYPE)
                    )
                );
                $context->addError($error);
            }
        }
        if (!$context->hasErrors()) {
            $context->setIncludedObjects($includedObjects);
        }
    }
}
