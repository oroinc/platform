<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Renderer;

use OpenApi\Annotations as OA;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Exception\RenderInvalidArgumentException;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Formatter\OpenApiFormatterInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Formatter\OpenApiFormatterRegistry;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Generator\OpenApiGeneratorRegistry;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Util;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;

/**
 * The main entry point to render OpenAPI specification.
 */
class OpenApiRenderer
{
    private array $generatorOptions;
    private OpenApiGeneratorRegistry $generatorRegistry;
    private OpenApiFormatterRegistry $formatterRegistry;
    private RestDocViewDetector $docViewDetector;

    public function __construct(
        array $generatorOptions,
        OpenApiGeneratorRegistry $generatorRegistry,
        OpenApiFormatterRegistry $formatterRegistry,
        RestDocViewDetector $docViewDetector
    ) {
        $this->generatorOptions = $generatorOptions;
        $this->generatorRegistry = $generatorRegistry;
        $this->formatterRegistry = $formatterRegistry;
        $this->docViewDetector = $docViewDetector;
    }

    public function getAvailableViews(): array
    {
        return $this->generatorRegistry->getViews();
    }

    public function getAvailableFormats(): array
    {
        return $this->formatterRegistry->getFormats();
    }

    /**
     * @throws RenderInvalidArgumentException If the view to dump is not valid or the requested format is not supported
     */
    public function render(string $view, string $format, array $options = []): string
    {
        $generator = $this->generatorRegistry->getGenerator($view);
        $formatter = $this->formatterRegistry->getFormatter($format);
        $generatorOptions = [];
        foreach ($this->generatorOptions as $optionName) {
            if (\array_key_exists($optionName, $options)) {
                $generatorOptions[$optionName] = $options[$optionName];
                unset($options[$optionName]);
            }
        }
        $this->docViewDetector->setView($view);
        try {
            return $this->renderApi($generator->generate($generatorOptions), $formatter, $options);
        } finally {
            $this->docViewDetector->setView();
        }
    }

    public function renderApi(OA\OpenApi $api, OpenApiFormatterInterface $formatter, array $options = []): string
    {
        $previousServers = $api->servers;
        try {
            $serverUrl = $options['server_url'] ?? null;
            if ($serverUrl) {
                $api->servers = [$this->createServer($api, $serverUrl)];
            }

            return $formatter->format($api);
        } finally {
            $api->servers = $previousServers;
        }
    }

    private function createServer(OA\OpenApi $api, string $url): OA\Server
    {
        $server = Util::createChildItem(OA\Server::class, $api);
        $server->url = $url;

        return $server;
    }
}
