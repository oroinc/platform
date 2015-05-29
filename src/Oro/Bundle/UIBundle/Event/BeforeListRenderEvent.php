<?php

namespace Oro\Bundle\UIBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormView;

use Twig_Environment;

class BeforeListRenderEvent extends Event
{
    /**
     * @var \Twig_Environment
     */
    protected $environment;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var FormView|null
     */
    protected $formView;

    /**
     * @param \Twig_Environment $environment
     * @param array $data
     * @param FormView|null $formView
     */
    public function __construct(Twig_Environment $environment, array $data, FormView $formView = null)
    {
        $this->formView    = $formView;
        $this->data        = $data;
        $this->environment = $environment;
    }

    /**
     * @return \Twig_Environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return FormView|null
     */
    public function getFormView()
    {
        return $this->formView;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return BeforeListRenderEvent
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }
}
