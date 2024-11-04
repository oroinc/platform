<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer;

use OpenApi\Annotations as OA;
use Oro\Bundle\ApiBundle\ApiDoc\DocumentationProviderInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Provider\OpenApiSpecificationNameProviderInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Util;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Adds documentation to OpenAPI specification.
 */
class DocumentationDescriber implements DescriberInterface
{
    private ?string $version;
    private ?string $documentationUri;
    private string $view;
    private array $requestType;
    private OpenApiSpecificationNameProviderInterface $titleProvider;
    private DocumentationProviderInterface $documentationProvider;

    public function __construct(
        ?string $version,
        ?string $documentationUri,
        string $view,
        array $requestType,
        OpenApiSpecificationNameProviderInterface $titleProvider,
        DocumentationProviderInterface $documentationProvider
    ) {
        $this->version = $version;
        $this->documentationUri = $documentationUri;
        $this->view = $view;
        $this->requestType = $requestType;
        $this->titleProvider = $titleProvider;
        $this->documentationProvider = $documentationProvider;
    }

    #[\Override]
    public function describe(OA\OpenApi $api, array $options): void
    {
        $api->info = Util::createChildItem(OA\Info::class, $api);
        $api->info->version = $this->version ?? '0.0.0';
        $api->info->title = $options['title'] ?? $this->titleProvider->getOpenApiSpecificationName($this->view);
        $documentation = $this->getDocumentation();
        if ($documentation) {
            $api->info->description = $documentation;
        }
    }

    private function getDocumentation(): string
    {
        $documentation = (string)$this->documentationProvider->getDocumentation(new RequestType($this->requestType));
        if ($this->documentationUri) {
            $documentationLink = sprintf('[The documentation](%s)', $this->documentationUri);
            if ($documentation) {
                $documentation = $documentationLink . "\n\n" . $documentation;
            } else {
                $documentation = $documentationLink;
            }
        }

        return $documentation;
    }
}
