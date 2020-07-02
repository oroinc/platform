<?php

namespace Oro\Bundle\SyncBundle\Cache;

use Doctrine\Common\Cache\Cache;

/**
 * Implements a cache for the Pub/Sub router.
 */
class PubSubRouterCache implements Cache
{
    /** @var string */
    private $directory;

    /** @var string */
    private $namespace;

    /** @var bool */
    private $debug;

    /** @var callable */
    private static $emptyErrorHandler;

    /**
     * @param string $directory
     * @param string $namespace
     * @param bool   $debug
     */
    public function __construct(string $directory, string $namespace, bool $debug = false)
    {
        $this->directory = $directory;
        $this->namespace = $namespace;
        $this->debug = $debug;
        self::$emptyErrorHandler = static function () {
        };
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        if ($this->debug) {
            return false;
        }

        $value = $this->includeFile($this->getFilename($id));
        if (null === $value) {
            return false;
        }

        if ($value['lifetime'] !== 0 && $value['lifetime'] < time()) {
            return false;
        }

        return $value['data'];
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        if ($this->debug) {
            return false;
        }

        $value = $this->includeFile($this->getFilename($id));
        if (null === $value) {
            return false;
        }

        return $value['lifetime'] === 0 || $value['lifetime'] > time();
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        if ($this->debug) {
            return true;
        }

        if ($lifeTime > 0) {
            $lifeTime = time() + $lifeTime;
        }

        return $this->writeFile(
            $this->getFilename($id),
            sprintf('<?php return %s;', var_export(['lifetime' => $lifeTime, 'data' => $data], true))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        if ($this->debug) {
            return true;
        }

        $filename = $this->getFilename($id);

        return @unlink($filename) || !file_exists($filename);
    }

    /**
     * {@inheritdoc}
     */
    public function getStats()
    {
        return null;
    }

    /**
     * Gets a file name
     *
     * @param string $id
     *
     * @return string
     */
    private function getFilename(string $id): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . $this->namespace . DIRECTORY_SEPARATOR . $id . '.php';
    }

    /**
     * @param string $filename
     *
     * @return array|null
     */
    private function includeFile(string $filename): ?array
    {
        // note: error suppression is still faster than `file_exists`, `is_file` and `is_readable`
        set_error_handler(self::$emptyErrorHandler);
        $value = include $filename;
        restore_error_handler();

        if (!isset($value['lifetime'])) {
            return null;
        }

        return $value;
    }

    /**
     * @param string $filename
     * @param string $content
     *
     * @return bool
     */
    private function writeFile(string $filename, string $content): bool
    {
        $directory = pathinfo($filename, PATHINFO_DIRNAME);
        if (!is_dir($directory) && @mkdir($directory, 0775, true) === false && !is_dir($directory)) {
            throw new \InvalidArgumentException(sprintf(
                'The directory "%s" does not exist and could not be created.',
                $directory
            ));
        }
        if (!is_writable($directory)) {
            throw new \InvalidArgumentException(sprintf('The directory "%s" is not writable.', $directory));
        }

        $tmpFile = tempnam($directory, 'swap');
        @chmod($tmpFile, 0664);
        if (file_put_contents($tmpFile, $content) !== false) {
            @chmod($tmpFile, 0664);
            if (@rename($tmpFile, $filename)) {
                return true;
            }

            @unlink($tmpFile);
        }

        return false;
    }
}
