<?php

namespace Oro\Bundle\UIBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormView;
use Twig_Environment;

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
     * @var \Twig_Environment
     */
    protected $twigEnvironment;

    /**
     * @param FormView          $form
     * @param array             $formData
     * @param \Twig_Environment $twigEnvironment
     * @param object|null       $entity
     */
    public function __construct(FormView $form, array $formData, Twig_Environment $twigEnvironment, $entity)
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

    /**
     * @param array $formData
     */
    public function setFormData(array $formData)
    {
        $this->formData = $formData;
    }

    /**
     * @return \Twig_Environment
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
