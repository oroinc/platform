<?php

namespace Oro\Bundle\UIBundle\Event;

use Oro\Bundle\UIBundle\View\ScrollData;
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
     * @var ScrollData
     */
    protected $scrollData;

    /**
     * @var object
     */
    protected $entity;

    /**
     * @var FormView|null
     */
    protected $formView;

    /**
     * @param \Twig_Environment $environment
     * @param ScrollData        $scrollData
     * @param object            $entity
     * @param FormView|null     $formView
     */
    public function __construct(
        Twig_Environment $environment,
        ScrollData $scrollData,
        $entity,
        FormView $formView = null
    ) {
        $this->environment = $environment;
        $this->scrollData = $scrollData;
        $this->entity = $entity;
        $this->formView = $formView;
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
     * @return ScrollData
     */
    public function getScrollData()
    {
        return $this->scrollData;
    }

    /**
     * @param ScrollData $scrollData
     * @return BeforeListRenderEvent
     */
    public function setScrollData(ScrollData $scrollData)
    {
        $this->scrollData = $scrollData;

        return $this;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
