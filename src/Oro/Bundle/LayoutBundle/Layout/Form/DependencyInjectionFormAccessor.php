<?php

namespace Oro\Bundle\LayoutBundle\Layout\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;

class DependencyInjectionFormAccessor extends AbstractFormAccessor
{
    /** @var ContainerInterface */
    protected $container;

    /** @var string */
    protected $formServiceId;

    /** @var FormInterface */
    protected $form;

    /**
     * @param ContainerInterface $container
     * @param string             $formServiceId
     */
    public function __construct(ContainerInterface $container, $formServiceId)
    {
        $this->container     = $container;
        $this->formServiceId = $formServiceId;
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
        return $this->formServiceId;
    }
}
