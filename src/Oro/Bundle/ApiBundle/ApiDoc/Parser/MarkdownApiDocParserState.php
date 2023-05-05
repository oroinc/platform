<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Parser;

/**
 * Represents a state of {@see MarkdownApiDocParser}.
 */
class MarkdownApiDocParserState
{
    private ?string $className = null;
    private ?string $section = null;
    private ?string $element = null;
    private ?string $subElement = null;
    private bool $hasSubElements = false;

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function setClassName(?string $className): void
    {
        $this->className = $className;
        $this->section = null;
        $this->element = null;
        $this->subElement = null;
        $this->hasSubElements = false;
    }

    public function getSection(): ?string
    {
        return $this->section;
    }

    public function setSection(?string $section): void
    {
        $this->section = $section;
        $this->element = null;
        $this->subElement = null;
        $this->hasSubElements = false;
    }

    public function getElement(): ?string
    {
        return $this->element;
    }

    public function setElement(?string $element): void
    {
        $this->element = $element;
        $this->subElement = null;
        $this->hasSubElements = false;
    }

    public function getSubElement(): ?string
    {
        return $this->subElement;
    }

    public function setSubElement(?string $subElement): void
    {
        $this->subElement = $subElement;
    }

    public function hasSubElements(): bool
    {
        return $this->hasSubElements;
    }

    public function setHasSubElements(bool $hasSubElements): void
    {
        $this->hasSubElements = $hasSubElements;
    }

    public function hasClass(): bool
    {
        return (bool)$this->className;
    }

    public function hasSection(): bool
    {
        return $this->section && $this->className;
    }

    public function hasElement(): bool
    {
        return $this->element && $this->section && $this->className;
    }
}
