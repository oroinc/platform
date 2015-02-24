<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator;

class GeneratorData
{
    /** @var string */
    protected $source;

    /** @var string */
    protected $filename;

    /**
     * @param array|string $source
     * @param string $filename
     */
    public function __construct($source, $filename = null)
    {
        $this->source   = $source;
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }
}
