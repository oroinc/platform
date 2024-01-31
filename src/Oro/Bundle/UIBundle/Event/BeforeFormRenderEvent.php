<?php

namespace Oro\Bundle\UIBundle\Event;

use Symfony\Component\Form\FormView;
use Symfony\Contracts\EventDispatcher\Event;
use Twig\Environment;

/**
 * BeforeFormRenderEvent event is triggered by oro_form_process Twig function
 * to allow for modification of the form data before it is rendered.
 */
class BeforeFormRenderEvent extends Event
{
    public function __construct(
        protected FormView $form,
        protected array $formData,
        protected Environment $twigEnvironment,
        protected ?object $entity = null,
        protected ?string $pageId = null
    ) {
    }

    /**
     * @return FormView
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return array
     */
    public function getFormData()
    {
        return $this->formData;
    }

    public function setFormData(array $formData)
    {
        $this->formData = $formData;
    }

    /**
     * @return Environment
     */
    public function getTwigEnvironment()
    {
        return $this->twigEnvironment;
    }

    /**
     * @return object|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    public function getPageId(): ?string
    {
        return $this->pageId;
    }
}
