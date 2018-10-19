<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Joins documentation from all child documentation providers.
 */
class ChainDocumentationProvider implements DocumentationProviderInterface
{
    /** @var DocumentationProviderInterface[] */
    private $providers;

    /**
     * @param DocumentationProviderInterface[] $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentation(RequestType $requestType): ?string
    {
        $paragraphs = [];
        foreach ($this->providers as $provider) {
            $documentation = $provider->getDocumentation($requestType);
            if ($documentation) {
                $paragraphs[] = $documentation;
            }
        }

        if (empty($paragraphs)) {
            return null;
        }

        return \implode("\n", $paragraphs);
    }
}
