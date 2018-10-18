<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\EntityIdResolverRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Builds documentation for predefined identifiers of Data API resources.
 */
class PredefinedIdDocumentationProvider implements DocumentationProviderInterface
{
    /** @var EntityIdResolverRegistry */
    private $entityIdResolverRegistry;

    /**
     * @param EntityIdResolverRegistry $entityIdResolverRegistry
     */
    public function __construct(EntityIdResolverRegistry $entityIdResolverRegistry)
    {
        $this->entityIdResolverRegistry = $entityIdResolverRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentation(RequestType $requestType): ?string
    {
        $descriptions = $this->entityIdResolverRegistry->getDescriptions($requestType);
        if (empty($descriptions)) {
            return null;
        }

        $items = [];
        foreach ($descriptions as $description) {
            $items[] = '- ' . $description;
        }

        return \sprintf($this->getTemplate(), \implode("\n", $items));
    }

    /**
     * @return string
     */
    private function getTemplate(): string
    {
        return <<<MARKDOWN
The following predefined identifiers are supported:

%s

All these identifiers can be used in a resource path, filters and request data.
MARKDOWN;
    }
}
