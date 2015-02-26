<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator;

class GeneratorData
{
    /** @var array|string */
    protected $source;

    /** @var string */
    protected $filename;

    /**
     * @param array|string $source
     * @param string       $filename
     */
    public function __construct($source, $filename = null)
    {
        $this->source   = $source;
        $this->filename = $filename;
    }

    /**
     * @return array|string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param array|string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
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
