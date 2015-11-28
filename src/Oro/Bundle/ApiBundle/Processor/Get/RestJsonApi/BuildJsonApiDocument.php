<?php

namespace Oro\Bundle\ApiBundle\Processor\Get\RestJsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilderFactory;

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
        /** @var GetContext $context */

        $documentBuilder = $this->documentBuilderFactory->createDocumentBuilder();

        if ($context->hasResult()) {
            $result = $context->getResult();
            if (null === $result) {
                $documentBuilder->setDataObject($result);
            } else {
                $documentBuilder->setDataObject($result, $context->getMetadata());
            }
        }

        $context->setResult($documentBuilder->getDocument());
    }
}
