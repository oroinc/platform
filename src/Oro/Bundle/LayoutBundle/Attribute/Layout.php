<?php

namespace Oro\Bundle\LayoutBundle\Attribute;

use Attribute;
use Oro\Bundle\PlatformBundle\Interface\PHPAttributeConfigurationInterface;

/**
 * The Layout class handles the #[Layout] attribute parts.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Layout implements PHPAttributeConfigurationInterface
{
    /**
     * $action - The controller action type.
     * $blockThemes - The block theme(s).
     * $theme - The layout theme name.
     * $vars - The layout context variables.
     */
    public function __construct(
        private string $action = '',
        private array|string $blockThemes = '',
        private string $theme = '',
        private ?array $vars = null,
    ) {
    }

    /**
     * Sets the controller action type.
     */
    public function setValue(string $action)
    {
        $this->action = $action;
    }

    /**
     * Returns the controller action type.
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * Sets the controller action type.
     */
    public function setAction(string $action)
    {
        $this->action = $action;
    }

    /**
     * Returns the layout theme name.
     */
    public function getTheme(): ?string
    {
        return $this->theme;
    }

    /**
     * Sets the layout theme name.
     */
    public function setTheme(string $theme)
    {
        $this->theme = $theme;
    }

    /**
     * Returns the block theme(s).
     */
    public function getBlockThemes(): array|string|null
    {
        return $this->blockThemes;
    }

    /**
     * Sets the block theme(s).
     */
    public function setBlockThemes(array|string $blockThemes)
    {
        $this->blockThemes = $blockThemes;
    }

    /**
     * Sets the block theme.
     */
    public function setBlockTheme(string $blockTheme)
    {
        $this->blockThemes = $blockTheme;
    }

    /**
     * Returns the layout context variables.
     */
    public function getVars(): ?array
    {
        return $this->vars;
    }

    /**
     * Sets the layout context variables.
     */
    public function setVars(null|array $vars)
    {
        $this->vars = $vars;
    }

    /**
     * Indicates whether all properties of the annotation are empty.
     */
    public function isEmpty(): bool
    {
        return
            empty($this->action)
            && empty($this->theme)
            && empty($this->blockThemes)
            && empty($this->vars);
    }

    #[\Override]
    public function getAliasName(): string
    {
        return 'layout';
    }

    #[\Override]
    public function allowArray(): bool
    {
        return false;
    }
}
