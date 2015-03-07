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
     * The controller action type.
     *
     * @var string
     */
    private $action;

    /**
     * The layout theme name.
     *
     * @var string
     */
    private $theme;

    /**
     * The block theme(s).
     *
     * @var array|string
     */
    private $blockThemes;

    /**
     * The layout context variables.
     *
     * @var string|null
     */
    private $vars;

    /**
     * Sets the controller action type.
     *
     * @param string $action
     */
    public function setValue($action)
    {
        $this->action = $action;
    }

    /**
     * Returns the controller action type.
     *
     * @return string|null
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Sets the controller action type.
     *
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * Returns the layout theme name.
     *
     * @return string|null
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Sets the layout theme name.
     *
     * @param string $theme
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;
    }

    /**
     * Returns the block theme(s).
     *
     * @return array|string|null
     */
    public function getBlockThemes()
    {
        return $this->blockThemes;
    }

    /**
     * Sets the block theme(s).
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
     * Returns the layout context variables.
     *
     * @return string[]|null
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * Sets the layout context variables.
     *
     * @param string[] $vars
     */
    public function setVars(array $vars)
    {
        $this->vars = $vars;
    }

    /**
     * Indicates whether all properties of the annotation are empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return
            empty($this->action)
            && empty($this->theme)
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
