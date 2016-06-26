<?php

namespace Oro\Component\Layout\Model;

class LayoutUpdateImport
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $root;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @param string $id
     * @param string $root
     * @param string $namespace
     */
    public function __construct($id, $root, $namespace)
    {
        $this->id = $id;
        $this->root = $root;
        $this->namespace = $namespace;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }
}
