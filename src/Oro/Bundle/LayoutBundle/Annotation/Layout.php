<?php

namespace Oro\Bundle\LayoutBundle\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;

/**
 * The Layout class handles the @Layout annotation parts.
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class Layout extends ConfigurationAnnotation
{
    const ALIAS = 'oro_layout';

    /**
     * The theme name.
     *
     * @var string
     */
    protected $theme;

    /**
     * The array of templates.
     *
     * @var array
     */
    protected $blockThemes = array();

    /**
     * The associative array of template variables.
     *
     * @var array
     */
    protected $vars = array();

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
     * Returns the array of block themes.
     *
     * @return array
     */
    public function getBlockThemes()
    {
        return $this->blockThemes;
    }

    /**
     * Sets the array of block themes.
     *
     * @param array $blockThemes
     */
    public function setBlockThemes($blockThemes)
    {
        $this->blockThemes = $blockThemes;
    }

    /**
     * Returns the array of template variables.
     *
     * @return array
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
    public function setVars($vars)
    {
        $this->vars = $vars;
    }

    /**
     * Returns the annotation alias name.
     *
     * @return string
     * @see ConfigurationInterface
     */
    public function getAliasName()
    {
        return self::ALIAS;
    }

    /**
     * Only one layout directive is allowed
     *
     * @return Boolean
     * @see ConfigurationInterface
     */
    public function allowArray()
    {
        return false;
    }
}
