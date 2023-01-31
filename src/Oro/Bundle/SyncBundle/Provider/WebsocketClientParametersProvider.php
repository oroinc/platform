<?php

namespace Oro\Bundle\SyncBundle\Provider;

use Oro\Bundle\SyncBundle\WebSocket\DsnBasedParameters;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Websocket client connection parameters provider retrieved from given DSN string.
 */
class WebsocketClientParametersProvider implements WebsocketClientParametersProviderInterface
{
    private const KNOWN_CONTEXT_OPTIONS = [
        'peer_name' => 'string',
        'verify_peer' => 'boolean',
        'verify_peer_name' => 'boolean',
        'allow_self_signed' => 'boolean',
        'cafile' => 'string',
        'capath' => 'string',
        'local_cert' => 'string',
        'local_pk' => 'string',
        'passphrase' => 'string',
        'CN_match' => 'string',
        'verify_depth' => 'integer',
        'ciphers' => 'string',
        'capture_peer_cert' => 'boolean',
        'capture_peer_cert_chain' => 'boolean',
        'SNI_enabled' => 'boolean',
        'SNI_server_name' => 'string',
        'disable_compression' => 'boolean',
        'peer_fingerprint' => 'array',
    ];

    private const FILTER_VAR_MAP = [
        'boolean' => FILTER_VALIDATE_BOOLEAN,
        'integer' => FILTER_VALIDATE_INT
    ];

    private string $host;

    private int $port;

    private string $path;

    /**
     * Any registered socket transport returned by http://php.net/manual/en/function.stream-get-transports.php
     */
    private string $transport;

    /**
     * Will be passed to a context create function http://php.net/manual/en/function.stream-context-create.php
     *
     * @var mixed
     */
    private $contextOptions;

    public function __construct(string $dsn)
    {
        $parametersBag = new DsnBasedParameters($dsn);

        $this->host = $parametersBag->getHost();
        $this->port = (int)$parametersBag->getPort();
        $this->path = $parametersBag->getPath();
        $this->transport = $parametersBag->getScheme();
        $this->contextOptions = $parametersBag->getParamValue('context_options') ?: [];

        $this->validateTransport();
        $this->validateAndNormalizeContextOptions();
    }

    public function getHost(): string
    {
        return $this->host;
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

    private function validateTransport(): void
    {
        if (!\in_array(
            $this->transport,
            stream_get_transports(),
            true
        )) {
            throw new InvalidConfigurationException(sprintf(
                'Transport "%s" is not available, please run stream_get_transports() to verify'
                . ' the list of registered transports.',
                $this->transport
            ));
        }
    }

    private function validateAndNormalizeContextOptions(): void
    {
        if (!is_array($this->contextOptions)) {
            throw new InvalidConfigurationException(
                'Socket context options must be an array'
            );
        }

        foreach ($this->contextOptions as $optionName => $optionValue) {
            if (!array_key_exists($optionName, self::KNOWN_CONTEXT_OPTIONS)) {
                throw new InvalidConfigurationException(sprintf(
                    'Unknown socket context option "%s". Only SSL context options '
                    . '(http://php.net/manual/en/context.ssl.php) are allowed.',
                    $optionName
                ));
            }

            $this->contextOptions[$optionName] = $this->validateAndNormalizeContextOptionByType(
                self::KNOWN_CONTEXT_OPTIONS[$optionName],
                $optionName,
                $optionValue
            );
        }
    }

    /**
     * @param string $optionType
     * @param string $optionName
     * @param $optionValue
     * @return array|mixed
     */
    private function validateAndNormalizeContextOptionByType(
        string $optionType,
        string $optionName,
        $optionValue
    ) {
        switch ($optionType) {
            case 'boolean':
            case 'integer':
                $normalizedValue =
                    filter_var($optionValue, self::FILTER_VAR_MAP[$optionType], FILTER_NULL_ON_FAILURE);
                if (null !== $normalizedValue) {
                    return $normalizedValue;
                }
                break;
            case 'array':
                if (is_array($optionValue)) {
                    return $optionValue;
                }
                break;
            case 'string':
                return (string)$optionValue;
        }

        throw new InvalidConfigurationException(sprintf(
            'Invalid type of socket context option "%s", expected "%s" type.',
            $optionName,
            $optionType
        ));
    }
}
