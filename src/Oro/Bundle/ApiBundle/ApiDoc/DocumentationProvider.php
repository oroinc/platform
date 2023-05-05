<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Loads a documentation from a specific Markdown file.
 *
 * Example of usage:
 * <code>
 *  services:
 *      acme.api_doc.documentation_provider.some_common_docs:
 *          class: Oro\Bundle\ApiBundle\ApiDoc\DocumentationProvider
 *          arguments:
 *              - '@@AcmeBundle/Resources/doc/api/some_common_docs.md'
 *              - '@file_locator'
 *          tags:
 *              - { name: oro.api.documentation_provider, requestType: json_api, priority: -10 }
 * <code>
 */
class DocumentationProvider implements DocumentationProviderInterface
{
    private string $resource;
    private FileLocatorInterface $fileLocator;

    public function __construct(string $resource, FileLocatorInterface $fileLocator)
    {
        $this->resource = $resource;
        $this->fileLocator = $fileLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentation(RequestType $requestType): ?string
    {
        if (false === strrpos($this->resource, '.md')) {
            throw new \InvalidArgumentException(sprintf(
                'The documentation resource "%s" must be a Markdown document.',
                $this->resource
            ));
        }

        return file_get_contents($this->fileLocator->locate($this->resource));
    }
}
