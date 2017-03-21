<?php
namespace Oro\Component\MessageQueue\Consumption\Dbal;

use Symfony\Component\Filesystem\Filesystem;

class DbalPidFileManager
{
    /**
     * @var string
     */
    private $pidDir;

    /**
     * @var string
     */
    private $pidFileExtension = '.pid';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param string $pidDir
     */
    public function __construct($pidDir)
    {
        $this->pidDir = $pidDir;
        $this->filesystem = new Filesystem();
    }

    /**
     * @param string $consumerId
     */
    public function createPidFile($consumerId)
    {
        $this->ensurePidDirExists();

        $filename = $this->generateFilenameByConsumerId($consumerId);

        $fHandler = @fopen($filename, 'x');
        if (false === $fHandler) {
            if ($this->filesystem->exists($filename)) {
                throw new \LogicException(sprintf('The pid file already exists. file:"%s"', $filename));
            } else {
                throw new \LogicException(sprintf('Cannot write the pid file. file:"%s"', $filename));
            }
        }

        $pid = getmypid();
        if (false === $pid) {
            throw new \LogicException(sprintf('Could not get my pid'));
        }

        $bytes = fwrite($fHandler, $pid);
        if (false === $bytes) {
            throw new \LogicException(sprintf('Could not write file. file:"%s"', $filename));
        }

        fclose($fHandler);
    }

    /**
     * @param string $consumerId
     */
    public function removePidFile($consumerId)
    {
        $this->filesystem->remove($this->generateFilenameByConsumerId($consumerId));
    }

    /**
     * @return array
     */
    public function getListOfPidsFileInfo()
    {
        $this->ensurePidDirExists();

        $pidsFileInfo = [];
        $path = rtrim($this->pidDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'*'.$this->pidFileExtension;
        /** @var \SplFileInfo $file */
        foreach (new \GlobIterator($path) as $file) {
            $content = file_get_contents($file->getPathname());
            if (false === $content) {
                continue;
            }

            $pid = trim($content);
            if (! is_numeric($pid)) {
                throw new \LogicException(sprintf('Expected numeric content. content:"%s"', $content));
            }

            $pidsFileInfo[] = [
                'pid' => (int) $pid,
                'consumerId' => $file->getBasename($this->pidFileExtension)
            ];
        }

        return $pidsFileInfo;
    }

    private function ensurePidDirExists()
    {
        if (!is_dir($this->pidDir)) {
            $this->filesystem->mkdir($this->pidDir, 0777, true);
            $this->filesystem->chmod($this->pidDir, 0777);
        }
    }

    /**
     * @param string $consumerId
     *
     * @return string
     */
    private function generateFilenameByConsumerId($consumerId)
    {
        return rtrim($this->pidDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$consumerId.$this->pidFileExtension;
    }
}
