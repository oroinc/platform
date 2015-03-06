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
     * The array of templates.
     *
     * @var array
     */
    protected $templates = array();

    /**
     * The action name.
     *
     * @var string
     */
    protected $action;

    /**
     * The theme name.
     *
     * @var string
     */
    protected $theme;

    /**
     * The associative array of template variables.
     *
     * @var array
     */
    protected $vars = array();

    /**
     * Returns the array of templates.
     *
     * @return array
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * Sets the array of templates.
     *
     * @param array $templates
     */
    public function setTemplates($templates)
    {
        $this->templates = $templates;
    }

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
     * Returns the action name.
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Sets the action name
     *
     * @param string $action The action name
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * Returns the array of templates variables.
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
