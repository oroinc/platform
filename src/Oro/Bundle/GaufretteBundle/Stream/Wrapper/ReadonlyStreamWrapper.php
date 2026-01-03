<?php

namespace Oro\Bundle\GaufretteBundle\Stream\Wrapper;

use Gaufrette\StreamWrapper;

/**
 * The stream wrapper implementation for the read-only Gaufrette filesystems.
 */
class ReadonlyStreamWrapper extends StreamWrapper
{
    #[\Override]
    public static function register($scheme = 'gaufrette-readonly')
    {
        static::streamWrapperUnregister($scheme);

        if (!static::streamWrapperRegister($scheme, __CLASS__)) {
            throw new \RuntimeException(sprintf(
                'Could not register stream wrapper class %s for scheme %s.',
                __CLASS__,
                $scheme
            ));
        }
    }

    // phpcs:disable
    #[\Override]
    public function url_stat($path, $flags)
    {
        $stream = $this->createStream($path);

        try {
            $stream->open($this->createStreamMode('r'));
        } catch (\RuntimeException $e) {
        }

        return $stream->stat();
    }
    // phpcs:enable

    // phpcs:disable
    #[\Override]
    public function stream_write($data)
    {
        throw new \LogicException('The Read-only stream does not allow write.');
    }
    // phpcs:enable

    // phpcs:disable
    #[\Override]
    public function stream_flush()
    {
        throw new \LogicException('The Read-only stream does not allow flush.');
    }
    // phpcs:enable

    #[\Override]
    public function unlink($path)
    {
        throw new \LogicException('The Read-only stream does not allow unlink.');
    }
}
