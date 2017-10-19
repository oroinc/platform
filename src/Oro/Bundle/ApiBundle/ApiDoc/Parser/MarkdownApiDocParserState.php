<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Parser;

class MarkdownApiDocParserState
{
    /** @var string|null */
    private $className;

    /** @var string|null */
    private $section;

    /** @var string|null */
    private $element;

    /** @var string|null */
    private $subElement;

    /** @var bool */
    private $hasSubElements = false;

    /**
     * @return string|null
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string|null $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
        $this->section = null;
        $this->element = null;
        $this->subElement = null;
        $this->hasSubElements = false;
    }

    /**
     * @return string|null
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * @param string|null $section
     */
    public function setSection($section)
    {
        $this->section = $section;
        $this->element = null;
        $this->subElement = null;
        $this->hasSubElements = false;
    }

    /**
     * @return string|null
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * @param string|null $element
     */
    public function setElement($element)
    {
        $this->element = $element;
        $this->subElement = null;
        $this->hasSubElements = false;
    }

    /**
     * @return string|null
     */
    public function getSubElement()
    {
        return $this->subElement;
    }

    /**
     * @param string|null $subElement
     */
    public function setSubElement($subElement)
    {
        $this->subElement = $subElement;
    }

    /**
     * @return bool
     */
    public function hasSubElements()
    {
        return $this->hasSubElements;
    }

    /**
     * @param bool $hasSubElements
     */
    public function setHasSubElements($hasSubElements)
    {
        $this->hasSubElements = $hasSubElements;
    }

    /**
     * @return bool
     */
    public function hasClass()
    {
        return (bool)$this->className;
    }

    /**
     * @return bool
     */
    public function hasSection()
    {
        return $this->section && $this->className;
    }

    /**
     * @return bool
     */
    public function hasElement()
    {
        return $this->element && $this->section && $this->className;
    }
}
