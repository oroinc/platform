<?php

namespace Oro\Bundle\LayoutBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractFormProvider
{
    /** @var array */
    protected $forms = [];

    /** @var array */
    protected $formViews = [];

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
     * @param string|FormTypeInterface $type
     * @param mixed                    $data
     * @param array                    $options
     * @param array                    $cacheKeyOptions
     *
     * @return FormInterface
     */
    protected function getForm($type, $data = null, array $options = [], array $cacheKeyOptions = [])
    {
        $cacheKey = $this->getCacheKey($type, $options, $cacheKeyOptions);

        if (!array_key_exists($cacheKey, $this->forms)) {
            $this->forms[$cacheKey] = $this->createForm($type, $data, $options);
        }

        return $this->forms[$cacheKey];
    }

    /**
     * Retrieve form view
     *
     * @param string|FormTypeInterface $type
     * @param mixed                    $data
     * @param array                    $options
     * @param array                    $cacheKeyOptions
     *
     * @return FormView
     */
    protected function getFormView($type, $data = null, array $options = [], array $cacheKeyOptions = [])
    {
        $cacheKey = $this->getCacheKey($type, $options, $cacheKeyOptions);
        if (!array_key_exists($cacheKey, $this->formViews)) {
            $form = $this->getForm($type, $data, $options, $cacheKeyOptions);

            $this->formViews[$cacheKey] = $form->createView();
        }

        return $this->formViews[$cacheKey];
    }

    /**
     * @param string|FormTypeInterface $type
     * @param null                     $data
     * @param array                    $options
     *
     * @return FormInterface
     */
    protected function createForm($type, $data = null, array $options = [])
    {
        return $this->formFactory->create($type, $data, $options);
    }

    /**
     * Generate Url
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    protected function generateUrl($name, array $arguments = [])
    {
        return $this->router->generate($name, $arguments);
    }

    /**
     * Get form cache key
     *
     * @param string|FormTypeInterface $type
     * @param array                    $formOptions
     * @param array                    $cacheKeyOptions
     *
     * @return string
     */
    protected function getCacheKey($type, array $formOptions = [], array $cacheKeyOptions = [])
    {
        $options = array_replace($formOptions, $cacheKeyOptions);
        ksort($options);

        if ($type instanceof FormTypeInterface) {
            $type = get_class($type);
        }

        return sprintf('%s:%s', $type, md5(serialize($options)));
    }
}
