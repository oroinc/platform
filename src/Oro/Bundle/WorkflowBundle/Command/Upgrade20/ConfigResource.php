<?php

namespace Oro\Bundle\WorkflowBundle\Command\Upgrade20;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

class ConfigResource
{
    /** @var \SplFileInfo */
    private $file;

    /** @var array */
    private $data;

    /** @var string */
    private $content;

    /** @var string */
    private $originalContent;

    /** @var bool */
    private $dry;

    public function __construct(\SplFileInfo $file, array $data, $dry = false)
    {
        $this->file = $file;
        $this->data = $data;
        $this->dry = (bool)$dry;
    }

    /**
     * @return \SplFileInfo
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    private function getRealContent()
    {
        if ($this->originalContent !== null) {
            return $this->originalContent;
        }

        return $this->originalContent = file_get_contents($this->file->getRealPath());
    }

    public function getContent()
    {
        if (null === $this->content) {
            $this->content = $this->getRealContent();
        }

        return $this->content;
    }

    /**
     * @param string $content
     */
    public function updateContent($content)
    {
        $this->content = $content;
    }

    public function dump(LoggerInterface $logger = null)
    {
        $logger = $logger ?: new NullLogger();

        if (!$this->content) {
            $logger->debug('No content changes at {resource} skipping.', ['resource' => $this->file->getRealPath()]);

            return;
        }

        if ($this->getRealContent() !== $this->content) {
            if ($this->dry) {
                $logger->debug(
                    'Dry mode. Skipping modification of config resource {file}',
                    ['file' => $this->file->getRealPath()]
                );

                return;
            }
            (new Filesystem())->dumpFile($this->file->getRealPath(), $this->content);
            $logger->info(
                'Config resource {resource} content updated.',
                ['resource' => $this->file->getRealPath()]
            );
            $logger->debug('Dumped content => {content}', ['content' => $this->content]);
        } else {
            $logger->debug(
                'Nothing changed in config resource {file}. Skipping.',
                ['file' => $this->file->getRealPath()]
            );
        }
    }
}
