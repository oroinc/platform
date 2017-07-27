<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Oro\Bundle\CacheBundle\Provider\PhpFileCache as BasePhpFileCache;

class WsseNoncePhpFileCache extends BasePhpFileCache
{
    /** Determines how often the purging of expired nonces is executed */
    const PURGE_INTERVAL = 300; // 5 minutes

    /** Determines how long the purging of expired nonces can be executed */
    const PURGE_MAX_TIME_FRAME = 3; // 3 seconds

    /** Determines how many files can be deleted at once */
    const PURGE_BATCH_SIZE = 100;

    /** Determines the length of directory names where cached data are located */
    const DIRECTORY_NAME_LENGTH = 1;

    /** @var int */
    protected $nonceLifeTime = 0;

    /**
     * Sets the lifetime of WSSE nonces
     *
     * @param int $lifetime
     */
    public function setNonceLifeTime($lifetime)
    {
        $this->nonceLifeTime = (int)$lifetime;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $this->purge();
        parent::doSave($id, $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFilename($id)
    {
        $namespace = $this->getNamespace();
        if ($namespace && strpos($id, $namespace) === 0) {
            $id = substr($id, strlen($namespace));
        }
        $id = $this->removeSpecialChars($id);

        return
            $this->getDataDirectory()
            . substr(hash('sha256', $id), 0, self::DIRECTORY_NAME_LENGTH)
            . DIRECTORY_SEPARATOR
            . $id
            . $this->getExtension();
    }

    /**
     * Gets the full path to the root directory where cached data are located.
     *
     * @return string
     */
    protected function getDataDirectory()
    {
        $result = $this->getDirectory() . DIRECTORY_SEPARATOR;

        $namespace = $this->getNamespace();
        if ($namespace) {
            $result .= $this->removeSpecialChars($namespace) . DIRECTORY_SEPARATOR;
        }

        return $result;
    }

    /**
     * Removes special characters like \/:? and others from the given string
     *
     * @param string $str
     *
     * @return string
     */
    protected function removeSpecialChars($str)
    {
        return preg_replace('@[\\\/:"*?<>|]+@', '', $str);
    }

    /**
     * Deletes expired nonces
     */
    protected function purge()
    {
        $startTime           = time();
        $directory           = $this->getDataDirectory();
        $purgeStatusFileName = 'cache_purge_status' . $this->getExtension();
        $purgeStatusFilePath = $directory . $purgeStatusFileName;

        // load the purge status
        $lastPurgeTime = @include $purgeStatusFilePath;
        if (false === $lastPurgeTime) {
            // initialize the purge status file
            $lastPurgeTime = $startTime;
            $this->writePurgeStatus($purgeStatusFilePath, $lastPurgeTime);
        }

        if ($startTime - $lastPurgeTime < self::PURGE_INTERVAL) {
            // exit because the purge interval is not elapsed yet
            return;
        }

        // delete expired nonces
        $lastPurgeTime = $startTime;
        if ($this->doPurge($directory, $startTime, $purgeStatusFileName)) {
            $this->writePurgeStatus($purgeStatusFilePath, $lastPurgeTime);
        }
    }

    /**
     * @param string $directory
     * @param int    $startTime
     * @param string $purgeStatusFileName
     *
     * @return bool TRUE if all expired nonces have been purged; otherwise, FALSE
     */
    protected function doPurge($directory, $startTime, $purgeStatusFileName)
    {
        $success      = true;
        $count        = 0;
        $fileIterator = $this->getExpiredFilesIterator($directory, $startTime - $this->nonceLifeTime);
        /** @var \SplFileInfo $file */
        foreach ($fileIterator as $name => $file) {
            if ($file->getFilename() === $purgeStatusFileName) {
                continue;
            }

            @unlink($name);
            $count++;
            if ($count % self::PURGE_BATCH_SIZE === 0 && time() - $startTime >= self::PURGE_MAX_TIME_FRAME) {
                $success = false;
                break;
            }
        }

        return $success;
    }

    /**
     * @param string $directory
     * @param int    $expirationTime
     *
     * @return \Iterator
     */
    protected function getExpiredFilesIterator($directory, $expirationTime)
    {
        $fileExtension    = $this->getExtension();
        $ignoreFilePrefix = substr(self::DOCTRINE_NAMESPACE_CACHEKEY, 0, -4); // remove ending "[%s]"

        return new \CallbackFilterIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            ),
            function (\SplFileInfo $file) use ($fileExtension, $ignoreFilePrefix, $expirationTime) {
                $fileName = $file->getFilename();

                return
                    substr($fileName, -strlen($fileExtension)) === $fileExtension
                    && $file->getMTime() <= $expirationTime
                    && strpos($fileName, $ignoreFilePrefix) !== 0;
            }
        );
    }

    /**
     * @param string $purgeStatusFilePath
     * @param int    $lastPurgeTime
     *
     * @return bool TRUE on success, FALSE if path cannot be created, if path is not writable or an any other error.
     */
    protected function writePurgeStatus($purgeStatusFilePath, $lastPurgeTime)
    {
        return $this->writeFile(
            $purgeStatusFilePath,
            sprintf('<?php return %d;', $lastPurgeTime)
        );
    }
}
