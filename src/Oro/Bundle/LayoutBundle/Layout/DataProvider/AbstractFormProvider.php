<?php

namespace Oro\Bundle\LayoutBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractFormProvider
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var UrlGeneratorInterface */
    protected $router;

    /**
     * @param FormFactoryInterface  $formFactory
     * @param UrlGeneratorInterface $router
     */
    public function __construct(FormFactoryInterface $formFactory, UrlGeneratorInterface $router)
    {
        $this->formFactory = $formFactory;
        $this->router = $router;
    }

    /**
     * Build new form
     *
     * @param string $formName
     * @param mixed $data
     * @param array $options
     *
     * @return FormInterface
     */
    protected function getForm($formName, $data = null, array $options = [])
    {
        return $this->formFactory->create($formName, $data, $options);
    }

    /**
     * Generate Url
     *
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    protected function generateUrl($name, $arguments = array())
    {
        return $this->router->generate($name, $arguments);
    }
}
