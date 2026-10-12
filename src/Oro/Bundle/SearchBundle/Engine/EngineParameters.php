<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Oro\Component\PhpUtils\DsnUtil;
use Symfony\Component\Mailer\Exception\InvalidArgumentException;

/**
 * Generalized search engine parameters bag built from given DSN string.
 * It's aware only of search engine name and connection options.
 */
class EngineParameters
{
    private ?string $engineName;

    private string $scheme;

    private ?array $engineNameAliases = [];

    private ?string $host;

    private ?string $port;

    private ?string $user;

    private ?string $password;

    private array $parameters = [];

    public function __construct(#[\SensitiveParameter] string $dsn)
    {
        $invalidDsnException = new InvalidArgumentException(
            sprintf('The "%s" search engine DSN is invalid.', DsnUtil::hideSecrets($dsn))
        );

        $parsedDsn = parse_url($dsn);
        if (false === $parsedDsn) {
            throw $invalidDsnException;
        }

        $path = trim($parsedDsn['path'] ?? '', '/');
        if (!empty($path)) {
            throw $invalidDsnException;
        }

        if (!isset($parsedDsn['scheme'])) {
            throw new InvalidArgumentException(sprintf(
                'The "%s" search engine DSN must contain a scheme.',
                DsnUtil::hideSecrets($dsn)
            ));
        }

        $this->scheme = $parsedDsn['scheme'];
        $this->engineName = str_replace('-', '_', $parsedDsn['scheme']);
        $this->host = $parsedDsn['host'] ?? null;
        $this->user = '' !== ($parsedDsn['user'] ?? '') ? urldecode($parsedDsn['user']) : null;
        $this->password = '' !== ($parsedDsn['pass'] ?? '') ? urldecode($parsedDsn['pass']) : null;
        $this->port = $parsedDsn['port'] ?? null;
        parse_str($parsedDsn['query'] ?? '', $this->parameters);
    }

    public function addEngineNameAlias(string $engineName, string $alias): void
    {
        $this->engineNameAliases[$alias] = $engineName;
    }

    public function getEngineName(): string
    {
        return $this->engineNameAliases[$this->engineName] ?? $this->engineName;
    }

    /**
     * The DSN scheme as written, for example "https". Unlike getEngineName(), it is not resolved through the aliases.
     */
    public function getScheme(): string
    {
        return $this->scheme;
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

    public function getPort(): ?string
    {
        return $this->port;
    }

    /**
     * @param string $key
     * @return mixed|null
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
