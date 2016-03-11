<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList\JsonApi;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Model\Error;
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

        try {
            if ($context->hasErrors()) {
                $documentBuilder->setErrorCollection($context->getErrors());
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
        } catch (\Exception $e) {
            $context->setResponseStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $error = new Error();
            $error->setInnerException($e);
            $documentBuilder = $this->documentBuilderFactory->createDocumentBuilder();
            $documentBuilder->setErrorObject($error);
            $context->setResult($documentBuilder->getDocument());
            $context->resetErrors();
        }
    }
}
