<?php

namespace Oro\Bundle\PlatformBundle\Profiler;

use Symfony\Component\HttpKernel\Profiler\FileProfilerStorage;
use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * Repeatable trying read profiler data
 */
class RepeatableFileProfilerStorage extends FileProfilerStorage
{
    private const TIMEOUT = 10;
    private const RETRY_SLEEP = 400;

    /**
     * {@inheritdoc}
     */
    public function read($token): ?Profile
    {
        if (!$token) {
            return null;
        }

        $file = $this->getFilename($token);
        $start = microtime(true);

        while (!file_exists($file) || !is_readable($file)) {
            if ((microtime(true) - $start) > self::TIMEOUT) {
                return null;
            }

            usleep(self::RETRY_SLEEP);
        }

        if (\function_exists('gzcompress')) {
            $file = 'compress.zlib://'.$file;
        }

        // Prevent notice on try unserialize partial content
        while (!$data = @unserialize(file_get_contents($file))) {
            if ((microtime(true) - $start) > self::TIMEOUT) {
                return null;
            }

            usleep(self::RETRY_SLEEP);
        }

        return $this->createProfileFromData($token, $data);
    }
}
