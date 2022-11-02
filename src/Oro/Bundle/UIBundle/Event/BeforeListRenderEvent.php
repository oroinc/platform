<?php

namespace Oro\Bundle\UIBundle\Event;

use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\EventDispatcher\Event;
use Twig\Environment;

/**
 * BeforeViewRenderEvent event is triggered by oro_ui_scroll_data_before Twig function
 * to collect data for a scroll block. See the scrollData macro for the data format.
 * @see platform/src/Oro/Bundle/UIBundle/Resources/views/macros.html.twig
 */
class BeforeListRenderEvent extends Event
{
    /**
     * @var Environment
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
     * @param Environment $environment
     * @param ScrollData        $scrollData
     * @param object            $entity
     * @param FormView|null     $formView
     */
    public function __construct(
        Environment $environment,
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
     * @return Environment
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
