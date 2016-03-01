<?php

namespace Oro\Bundle\IntegrationBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormView;

class LoadIntegrationThemesEvent extends Event
{
    const NAME = 'oro_integration.load_integration_themes';

    /** @var FormView */
    protected $formView;

    /** @var array */
    protected $themes;

    /**
     * @param FormView $formView
     * @param array    $themes
     */
    public function __construct(FormView $formView, array $themes = [])
    {
        $this->formView = $formView;
        $this->themes = $themes;
    }

    /**
     * @return FormView
     */
    public function getFormView()
    {
        return $this->formView;
    }

    /**
     * @return array
     */
    public function getThemes()
    {
        return $this->themes;
    }

    /**
     * @param string $theme
     */
    public function addTheme($theme)
    {
        $this->themes[] = $theme;
    }

    /**
     * @param array $themes
     */
    public function setThemes(array $themes)
    {
        $this->themes = $themes;
    }
}
