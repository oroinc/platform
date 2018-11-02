<?php

namespace Oro\Component\Testing;

use Symfony\Component\Filesystem\Filesystem;

/**
 * This trait can be used in any types of tests, e.g. unit or functional tests,
 * in case they need to work with a temporary directories or files.
 */
trait TempDirExtension
{
    /** @var string[] */
    private $tempDirs = [];

    /**
     * Removes all temporary directories requested via getTempDir() method.
     *
     * @after
     */
    protected function removeTempDirs()
    {
        if (empty($this->tempDirs)) {
            return;
        }

        try {
            $fs = new Filesystem();
            foreach ($this->tempDirs as $dir) {
                if ($fs->exists($dir)) {
                    $fs->remove($dir);
                }
            }
        } finally {
            $this->tempDirs = [];
        }
    }

    /**
     * Gets a temporary directory.
     *
     * @param string    $subDir
     * @param bool|null $existence TRUE to make sure that the directory exists and empty
     *                             FALSE to make sure that the directory does not exist
     *                             NULL to not check the directory existence
     *
     * @return string The full path of the temporary directory
     */
    protected function getTempDir(string $subDir, ?bool $existence = true): string
    {
        $subDir = preg_replace('/^[\/\\\\]+(.*)/', '$1', $subDir);
        if (0 !== strpos(strtolower($subDir), 'oro')) {
            $subDir = 'oro_' . $subDir;
        }

        $tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $subDir;
        if (!in_array($tmpDir, $this->tempDirs, true)) {
            $this->tempDirs[] = $tmpDir;
        }

        if (null !== $existence) {
            $fs = new Filesystem();
            if ($fs->exists($tmpDir)) {
                $fs->remove($tmpDir);
            }
            if ($existence) {
                $fs->mkdir($tmpDir);
            }
        }

        return $tmpDir;
    }

    /**
     * Gets a temporary file.
     *
     * @param string $subDir
     * @param string $prefix
     *
     * @return string The full path of a file in the temporary directory
     */
    protected function getTempFile(string $subDir, string $prefix = 'tmp'): string
    {
        return
            $this->getTempDir($subDir)
            . DIRECTORY_SEPARATOR
            . str_replace('.', '', uniqid($prefix, true));
    }

    /**
     * Copies the content from $sourceDir directory to a temporary directory.
     *
     * @param string $subDir
     * @param string $sourceDir
     *
     * @return string The full path of the temporary directory
     */
    protected function copyToTempDir(string $subDir, string $sourceDir): string
    {
        $tmpDir = $this->getTempDir($subDir, false);
        $fs = new Filesystem();
        $fs->mkdir($tmpDir);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $target = $tmpDir . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                $fs->mkdir($target);
            } else {
                $fs->copy($item, $target);
            }
        }

        return $tmpDir;
    }
}
