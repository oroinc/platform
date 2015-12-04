<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilderFactory;

/**
 * Builds JSON API response based on the Context state.
 */
class BuildJsonApiDocument implements ProcessorInterface
{
    /** @var JsonApiDocumentBuilderFactory */
    protected $documentBuilderFactory;

    /**
     * @param JsonApiDocumentBuilderFactory $documentBuilderFactory
     */
    public function __construct(JsonApiDocumentBuilderFactory $documentBuilderFactory)
    {
        $this->documentBuilderFactory = $documentBuilderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetListContext $context */

        $documentBuilder = $this->documentBuilderFactory->createDocumentBuilder();

        if ($context->hasErrors()) {
            $documentBuilder->setErrors($context->getErrors());
            // remove errors from the Context to avoid processing them by other processors
            $context->resetErrors();
        } elseif ($context->hasResult()) {
            $result = $context->getResult();
            if (empty($result)) {
                $documentBuilder->setDataCollection($result);
            } else {
                $documentBuilder->setDataCollection($result, $context->getMetadata());
            }
        }

        $context->setResult($documentBuilder->getDocument());
    }
}
