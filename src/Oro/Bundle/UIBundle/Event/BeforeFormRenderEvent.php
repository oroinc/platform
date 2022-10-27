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
    /**
     * @var FormView
     */
    protected $form;

    /**
     * Array of form data collected in entity update template
     *
     * @var array
     */
    protected $formData;

    /**
     * @var object|null
     */
    protected $entity;

    /**
     * @var Environment
     */
    protected $twigEnvironment;

    /**
     * @param FormView          $form
     * @param array             $formData
     * @param Environment       $twigEnvironment
     * @param object|null       $entity
     */
    public function __construct(FormView $form, array $formData, Environment $twigEnvironment, $entity)
    {
        $this->form = $form;
        $this->formData = $formData;
        $this->twigEnvironment = $twigEnvironment;
        $this->entity = $entity;
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
}
