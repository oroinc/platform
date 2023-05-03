<?php

namespace Oro\Component\MessageQueue\Transport;

/**
 * DSN string based parameters bag to configure MQ transport.
 */
class DsnBasedParameters
{
    private ?string $transportName;

    private ?string $host;

    private ?int $port;

    private ?string $user;

    private ?string $password;

    private ?string $path;

    private array $parameters = [];

    public function __construct(string $dsn)
    {
        $invalidDsnException =
            new \InvalidArgumentException(sprintf(
                'The "%s" message queue transport connection DSN is invalid.',
                $dsn
            ));

        $dsn = strtr($dsn, [
            'amqp:///' => 'amqp://localhost/',
            'amqp://:' => 'amqp://localhost:',
        ]);

        $parsedDsn = parse_url($dsn);
        if (false === $parsedDsn) {
            throw $invalidDsnException;
        }

        if (!isset($parsedDsn['scheme'])) {
            throw new \InvalidArgumentException(sprintf(
                'The "%s" message queue transport connection DSN must contain a scheme.',
                $dsn
            ));
        }

        $this->path = ltrim($parsedDsn['path'] ?? '', '/');

        if ($this->path) {
            $this->path = urldecode($this->path);
        }

        $this->transportName = str_replace('-', '_', $parsedDsn['scheme']);
        $this->host = $parsedDsn['host'] ?? null;
        $this->user = '' !== ($parsedDsn['user'] ?? '') ? urldecode($parsedDsn['user']) : null;
        $this->password = '' !== ($parsedDsn['pass'] ?? '') ? urldecode($parsedDsn['pass']) : null;
        $this->port = ($parsedDsn['port'] ?? null) ? (int) $parsedDsn['port'] : null;

        parse_str($parsedDsn['query'] ?? '', $this->parameters);
    }

    public function getTransportName(): string
    {
        return $this->transportName;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getParamValue(string $key)
    {
        return $this->parameters[$key] ?? null;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
