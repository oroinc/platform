<?php

namespace Oro\Bundle\SyncBundle\Client\Wamp\Factory;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * This class represents connection attributes which will be used to create an instance of WampClient.
 */
class ClientAttributes
{
    /** @var string */
    private $host;

    /** @var int */
    private $port;

    /** @var string */
    private $path;

    /**
     * Any registered socket transport returned by http://php.net/manual/en/function.stream-get-transports.php
     *
     * @var string
     */
    private $transport;

    /**
     * Will be passed to a context create function http://php.net/manual/en/function.stream-context-create.php
     *
     * @var array
     */
    private $contextOptions;

    private ?ConfigManager $configManager = null;

    /**
     * user_agent parameter for websocket connection header
     */
    private ?string $userAgent = null;

    public function __construct(
        string $host,
        int $port,
        string $path,
        string $transport,
        array $contextOptions
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->transport = $transport;
        $this->contextOptions = $contextOptions;
    }

    public function getHost(): string
    {
        $host = $this->host === '*' ? '127.0.0.1' : $this->host;

        if (!is_null($this->configManager) && $this->host === '*') {
            $appUrl = $this->configManager->get('oro_ui.application_url');
            $parsedAppUrl = parse_url($appUrl);
            $host = $parsedAppUrl['host'];
        }

        return $host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getTransport(): string
    {
        return $this->transport;
    }

    public function getContextOptions(): array
    {
        return $this->contextOptions;
    }

    public function setConfigManager(ConfigManager $configManager): void
    {
        $this->configManager = $configManager;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }
}
