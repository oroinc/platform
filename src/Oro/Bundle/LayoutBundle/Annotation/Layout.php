<?php

namespace Oro\Bundle\LayoutBundle\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * The Layout class handles the @Layout annotation parts.
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class Layout extends ConfigurationAnnotation
{
    /**
     * The theme name.
     *
     * @var string
     */
    private $theme;

    /**
     * The array of the block theme(s).
     *
     * @var array|string
     */
    private $blockThemes;

    /**
     * The associative array of template variables.
     *
     * @var string|null
     */
    private $vars;

    /**
     * Returns the theme name.
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Sets the theme name
     *
     * @param string $theme The theme name
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;
    }

    /**
     * Returns block theme(s).
     *
     * @return array|string|null
     */
    public function getBlockThemes()
    {
        return $this->blockThemes;
    }

    /**
     * Sets block theme(s).
     *
     * @param array|string $blockThemes
     */
    public function setBlockThemes($blockThemes)
    {
        $this->blockThemes = $blockThemes;
    }

    /**
     * Sets the block theme.
     *
     * @param string $blockTheme
     */
    public function setBlockTheme($blockTheme)
    {
        $this->blockThemes = $blockTheme;
    }

    /**
     * Returns the array of template variables.
     *
     * @return string[]|null
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * Sets the template variables
     *
     * @param array $vars The template variables
     */
    public function setVars(array $vars)
    {
        $this->vars = $vars;
    }

    /**
     * Indicates whether all properties of the annotation are empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return
            empty($this->theme)
            && empty($this->blockThemes)
            && empty($this->vars);
    }

    /**
     * {@inheritdoc}
     */
    public function getAliasName()
    {
        return 'layout';
    }

    /**
     * {@inheritdoc}
     */
    public function allowArray()
    {
        return false;
    }
}
