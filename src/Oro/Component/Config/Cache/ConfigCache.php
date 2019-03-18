<?php

namespace Oro\Component\Config\Cache;

use Symfony\Component\Config\Resource\SelfCheckingResourceChecker;
use Symfony\Component\Config\ResourceCheckerConfigCache;

/**
 * This class uses a file on disk to cache configuration data.
 * Unlike Symfony's ConfigCache, this implementation does not write metadata when debugging is disabled.
 */
class ConfigCache extends ResourceCheckerConfigCache
{
    /** @var bool */
    private $debug;

    /**
     * @param string $file  The absolute cache path
     * @param bool   $debug Whether debugging is enabled or not
     */
    public function __construct(string $file, bool $debug)
    {
        $this->debug = $debug;

        $checkers = [];
        if ($this->debug) {
            $checkers = [new SelfCheckingResourceChecker()];
        }

        parent::__construct($file, $checkers);
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh()
    {
        if (!$this->debug && \is_file($this->getPath())) {
            return true;
        }

        return parent::isFresh();
    }

    /**
     * {@inheritdoc}
     */
    public function write($content, array $metadata = null)
    {
        if (!$this->debug && null !== $metadata) {
            $metadata = null;
        }

        parent::write($content, $metadata);
    }
}
