<?php

namespace Oro\Bundle\SyncBundle\WebSocket;

/**
 * Generic DSN-based parameters bag of websocket configuration.
 */
class DsnBasedParameters
{
    private string $schema;

    private string $host;

    private ?int $port;

    private string $path;

    private array $parameters = [];

    public function __construct(string $dsn)
    {
        $invalidDsnException =
            new \InvalidArgumentException(sprintf(
                'The "%s" websocket related config DSN string is invalid.',
                $dsn
            ));

        $parsedDsn = parse_url($dsn);
        if (false === $parsedDsn) {
            throw $invalidDsnException;
        }

        $this->schema = $parsedDsn['scheme'] ?? 'tcp';
        $this->host = $parsedDsn['host'] ?? '*';
        $this->port = isset($parsedDsn['port']) ? (int)$parsedDsn['port'] : null;
        $this->path = trim($parsedDsn['path'] ?? '', '/');
        parse_str($parsedDsn['query'] ?? '', $this->parameters);
    }

    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->schema;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getParamValue(string $key)
    {
        return $this->parameters[$key] ?? null;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
