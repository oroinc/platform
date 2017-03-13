<?php
namespace Oro\Component\MessageQueue\Consumption\Dbal;

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
     * @param string $pidDir
     */
    public function __construct($pidDir)
    {
        $this->pidDir = $pidDir;
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
            throw new \LogicException(sprintf('Pid file already exists. file:"%s"', $filename));
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
        @unlink($this->generateFilenameByConsumerId($consumerId));
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
        @mkdir($this->pidDir, 0777, true);
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
