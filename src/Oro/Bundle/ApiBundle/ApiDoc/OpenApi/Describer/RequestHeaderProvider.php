<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer;

use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;

/**
 * Provides request headers for a specific API resource.
 */
class RequestHeaderProvider implements RequestHeaderProviderInterface
{
    private array $headers;
    private RestDocViewDetector $docViewDetector;
    private array $actionHeaders = [];

    public function __construct(array $headers, RestDocViewDetector $docViewDetector)
    {
        $this->headers = $headers;
        $this->docViewDetector = $docViewDetector;
    }

    #[\Override]
    public function getRequestHeaders(string $action, ?string $entityType, ?string $associationName): array
    {
        $view = $this->docViewDetector->getView();
        $headers = $this->actionHeaders[$view][$action] ?? null;
        if (null === $headers) {
            $headers = $this->loadRequestHeaders($view, $action);
            $this->actionHeaders[$view][$action] = $headers;
        }

        return $headers;
    }

    private function loadRequestHeaders(string $view, string $action): array
    {
        $headers = [];
        $viewHeaders = $this->headers[$view] ?? [];
        foreach ($viewHeaders as $name => $items) {
            $headerValue = $this->resolveHeaderValue($action, $items);
            if (null !== $headerValue) {
                $headers[$name] = $headerValue
                    ? ['example' => implode(';', $headerValue)]
                    : [];
            }
        }

        return $headers;
    }

    private function resolveHeaderValue(string $action, array $items): ?array
    {
        $headerValue = null;
        foreach ($items as $item) {
            $actions = $item['actions'] ?? [];
            if ($actions && (!$action || !\in_array($action, $actions, true))) {
                continue;
            }
            $headerValue = [];
            $value = $item['value'] ?? null;
            if (null !== $value) {
                $headerValue[] = $value;
            }
        }

        return $headerValue;
    }
}
