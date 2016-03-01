<?php

namespace Oro\Bundle\LayoutBundle\Layout\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;

class DependencyInjectionFormAccessor extends AbstractFormAccessor implements ConfigurableFormAccessorInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var string */
    protected $formServiceId;

    /** @var FormInterface */
    protected $form;

    /** @var string */
    protected $hash;

    /**
     * @param ContainerInterface $container     The DI container
     * @param string             $formServiceId The id of the form service in DI container
     * @param FormAction|null    $action        The submit action of the form
     * @param string|null        $method        The submit method of the form
     * @param string|null        $enctype       The encryption type of the form
     */
    public function __construct(
        ContainerInterface $container,
        $formServiceId,
        FormAction $action = null,
        $method = null,
        $enctype = null
    ) {
        $this->container     = $container;
        $this->formServiceId = $formServiceId;
        $this->action        = $action;
        $this->method        = $method;
        $this->enctype       = $enctype;

        $this->hash = $this->buildHash($formServiceId, $action, $method, $enctype);
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        if (!$this->form) {
            $this->form = $this->container->get($this->formServiceId);
        }

        return $this->form;
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return $this->hash;
    }

    /**
     * @param mixed $formData
     */
    public function setFormData($formData)
    {
        if (!$this->getForm()->isSubmitted()) {
            $this->getForm()->setData($formData);
        }
    }
}
