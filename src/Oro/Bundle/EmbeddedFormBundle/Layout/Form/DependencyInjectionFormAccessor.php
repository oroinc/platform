<?php

namespace Oro\Bundle\EmbeddedFormBundle\Layout\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;

/**
 * This class retrieves a form from the Symfony dependency injection container
 * and generates a unique hash for its configuration.
 */
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
     * @param FormAction|null $action        The submit action of the form
     * @param string|null        $method        The submit method of the form
     * @param string|null        $enctype       The encryption type of the form
     */
    public function __construct(
        ContainerInterface $container,
        $formServiceId,
        ?FormAction $action = null,
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

    #[\Override]
    public function getForm()
    {
        if (!$this->form) {
            $this->form = $this->container->get($this->formServiceId);
        }

        return $this->form;
    }

    #[\Override]
    public function toString()
    {
        return 'form_service_id:' . $this->formServiceId;
    }

    /**
     * @param mixed $formData
     */
    #[\Override]
    public function setFormData($formData)
    {
        if (!$this->getForm()->isSubmitted()) {
            $this->getForm()->setData($formData);
        }
    }

    #[\Override]
    public function getHash()
    {
        return $this->hash;
    }
}
