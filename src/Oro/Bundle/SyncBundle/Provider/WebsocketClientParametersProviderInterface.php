<?php

namespace Oro\Bundle\SyncBundle\Provider;

/**
 * Websocket client connection parameters provider interface.
 */
interface WebsocketClientParametersProviderInterface
{
    public function getHost(): string;

    public function getPort(): int;

    public function getPath(): string;

    public function getTransport(): string;

    public function getContextOptions(): array;
}
