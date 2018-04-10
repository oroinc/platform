<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The factory that creates the document builder for a specific request type.
 * The implementation of this factory assumes that all document builders
 * are declared in DIC as public non shared services.
 * @see \Oro\Bundle\ApiBundle\DependencyInjection\Compiler\DocumentBuilderCompilerPass
 */
class DocumentBuilderFactory
{
    /** @var array [[document builder service id, request type expression], ...] */
    private $documentBuilders;

    /** @var ContainerInterface */
    private $container;

    /** @var RequestExpressionMatcher */
    private $matcher;

    /**
     * @param array                    $documentBuilders [[document builder service id, request type expression], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(
        array $documentBuilders,
        ContainerInterface $container,
        RequestExpressionMatcher $matcher
    ) {
        $this->documentBuilders = $documentBuilders;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Creates a new instance of DocumentBuilderInterface
     * responsible to build a document for the specific request type.
     *
     * @param RequestType $requestType
     *
     * @return DocumentBuilderInterface
     *
     * @throws \LogicException if a document builder cannot be created for the given request type
     */
    public function createDocumentBuilder(RequestType $requestType): DocumentBuilderInterface
    {
        $documentBuilderServiceId = null;
        foreach ($this->documentBuilders as list($serviceId, $expression)) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $documentBuilderServiceId = $serviceId;
                break;
            }
        }
        if (null === $documentBuilderServiceId) {
            throw new \LogicException(
                sprintf('Cannot find a document builder for the request "%s".', (string)$requestType)
            );
        }

        return $this->container->get($documentBuilderServiceId);
    }
}
