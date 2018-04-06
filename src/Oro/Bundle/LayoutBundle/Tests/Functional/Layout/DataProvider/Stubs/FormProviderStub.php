<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional\Layout\DataProvider\Stubs;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class FormProviderStub extends AbstractFormProvider
{
    /**
     * @param string $routeName
     * @param mixed  $data
     * @param array  $options
     * @param array  $cacheKeyOptions
     *
     * @return FormInterface
     */
    public function getTestForm(
        $routeName,
        $data = null,
        array $options = [],
        array $cacheKeyOptions = []
    ) {
        $options = array_merge($this->getFormOptions($routeName), $options);

        return $this->getForm(LayoutFormStub::class, $data, $options, $cacheKeyOptions);
    }

    /**
     * @param string $routeName
     * @param mixed  $data
     * @param array  $options
     * @param array  $cacheKeyOptions
     *
     * @return FormView
     */
    public function getTestFormView(
        $routeName,
        $data = null,
        array $options = [],
        array $cacheKeyOptions = []
    ) {
        $options = array_merge($this->getFormOptions($routeName), $options);

        return $this->getFormView(LayoutFormStub::class, $data, $options, $cacheKeyOptions);
    }

    /**
     * @param array  $formOptions
     * @param array  $cacheKeyOptions
     *
     * @return string
     */
    public function getTestCacheKey(array $formOptions = [], array $cacheKeyOptions = [])
    {
        return $this->getCacheKey(LayoutFormStub::class, $formOptions, $cacheKeyOptions);
    }

    /**
     * @param string $routeName
     *
     * @return array
     */
    private function getFormOptions($routeName)
    {
        $options['action'] = $this->generateUrl($routeName);

        return $options;
    }
}
