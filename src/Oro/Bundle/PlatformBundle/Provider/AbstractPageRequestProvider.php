<?php

namespace Oro\Bundle\PlatformBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Base page request provider.
 */
abstract class AbstractPageRequestProvider implements PageRequestProviderInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private ConfigManager $configManager,
        private LoggerInterface $logger
    ) {
    }

    public function createRequest(string $method, string $route, array $parameters = []): ?Request
    {
        try {
            $routePath = $this->urlGenerator->generate($route, $parameters);
        } catch (\Throwable $exception) {
            // skip request if route does not exits
            $this->logger->warning(
                'Failed to generate url by route: {message}',
                ['message' => $exception->getMessage(), 'exception' => $exception]
            );

            return null;
        }
        $uri = $this->configManager->get('oro_ui.application_url') . $routePath;
        $symfonyRequest = Request::create($uri, $method);
        $symfonyRequest->setRequestFormat('html');

        return $symfonyRequest;
    }
}
