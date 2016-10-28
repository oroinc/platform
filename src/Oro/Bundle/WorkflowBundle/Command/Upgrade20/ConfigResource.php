<?php

namespace Oro\Bundle\WorkflowBundle\Command\Upgrade20;

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

    public function dump()
    {
        if ($this->dry) {
            return;
        }
        if (!$this->content) {
            return;
        }

        if ($this->getRealContent() !== $this->content) {
            file_put_contents($this->file->getRealPath(), $this->content);
        }
    }
}
