<?php

namespace Oro\Component\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;

abstract class AbstractFormProvider
{
    /** @var array */
    protected $forms = [];

    /** @var FormFactoryInterface */
    protected $formFactory;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * Get form accessor with new form
     *
     * @param string $formName
     * @param string $routeName
     * @param mixed $data
     * @param array $parameters
     * @param array $options
     *
     * @return FormAccessor
     */
    protected function getFormAccessor(
        $formName,
        $routeName = null,
        $data = null,
        array $parameters = [],
        array $options = []
    ) {
        $cacheKey = $this->getCacheKey($formName, $routeName, $parameters);
        if (!array_key_exists($cacheKey, $this->forms)) {
            $this->forms[$cacheKey] = new FormAccessor(
                $this->getForm($formName, $data, $options),
                $routeName ? FormAction::createByRoute($routeName, $parameters) : null
            );
        }

        return $this->forms[$cacheKey];
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
     * Get form cache key
     *
     * @param string $formName
     * @param string $routeName
     * @param array $parameters
     *
     * @return string
     */
    protected function getCacheKey($formName, $routeName, array $parameters = [])
    {
        return sprintf('%s:%s:%s', $formName, $routeName, implode(':', $parameters));
    }
}
