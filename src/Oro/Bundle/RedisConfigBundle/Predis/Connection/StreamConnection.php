<?php

declare(strict_types=1);

namespace Oro\Bundle\RedisConfigBundle\Predis\Connection;

use Predis\Connection\ParametersInterface;
use Predis\Connection\StreamConnection as BaseStreamConnection;

/**
 * Connects to Redis over TLS with a stream context that only this connection uses.
 *
 * The parent class opens the socket with the default stream context of PHP and then sets the ssl options of the
 * connection on the context of the socket, which is the default context. Thus the ssl options of one Redis DSN, for
 * example ssl[verify_peer]=0 or ssl[cafile], apply to all the connections and HTTPS requests of the process that
 * use the default context.
 *
 * {@see \Oro\Bundle\RedisConfigBundle\Predis\Configuration\ConnectionFactoryOption} uses this class for the "tls"
 * and "rediss" schemes.
 */
class StreamConnection extends BaseStreamConnection
{
    /**
     * The same as the parent method, but the socket gets a new stream context.
     * The new context starts with the options of the default context, as the socket of the parent method does.
     */
    #[\Override]
    protected function createStreamSocket(ParametersInterface $parameters, $address, $flags)
    {
        $timeout = isset($parameters->timeout) ? (float) $parameters->timeout : 5.0;
        $context = stream_context_create(stream_context_get_options(stream_context_get_default()));

        $resource = @stream_socket_client($address, $errno, $errstr, $timeout, $flags, $context);
        if (!$resource) {
            $this->onConnectionError(trim($errstr), $errno);
        }

        if (isset($parameters->read_write_timeout)) {
            $readWriteTimeout = (float) $parameters->read_write_timeout;
            $readWriteTimeout = $readWriteTimeout > 0 ? $readWriteTimeout : -1;
            $timeoutSeconds = (int) floor($readWriteTimeout);
            $timeoutMicroseconds = (int) (($readWriteTimeout - $timeoutSeconds) * 1000000);
            stream_set_timeout($resource, $timeoutSeconds, $timeoutMicroseconds);
        }

        if (isset($parameters->tcp_nodelay) && \function_exists('socket_import_stream')) {
            $socket = socket_import_stream($resource);
            socket_set_option($socket, SOL_TCP, TCP_NODELAY, (int) $parameters->tcp_nodelay);
        }

        return $resource;
    }
}
