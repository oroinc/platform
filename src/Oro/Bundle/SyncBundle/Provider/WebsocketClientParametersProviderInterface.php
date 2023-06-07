<?php

namespace Oro\Bundle\SyncBundle\Provider;

/**
 * Websocket client connection parameters provider interface.
 *
 * @method null|string getUserAgent()
 */
interface WebsocketClientParametersProviderInterface
{
    public function getHost(): string;

    public function getPort(): int;

    public function getPath(): string;

    public function getTransport(): string;

    public function getContextOptions(): array;
}
