<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional\Layout\DataProvider\Stubs;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Component\Testing\Unit\Form\Type\Stub\FormStub;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class FormProviderStub extends AbstractFormProvider
{
    /**
     * @param string $formName
     * @param string $routeName
     * @param mixed  $data
     * @param array  $options
     * @param array  $cacheKeyOptions
     *
     * @return FormInterface
     */
    public function getTestForm(
        $formName,
        $routeName,
        $data = null,
        array $options = [],
        array $cacheKeyOptions = []
    ) {
        $options = array_merge($this->getFormOptions($routeName), $options);

        $form = new FormStub($formName);

        return $this->getForm($form, $data, $options, $cacheKeyOptions);
    }

    /**
     * @param string $formName
     * @param string $routeName
     * @param mixed  $data
     * @param array  $options
     * @param array  $cacheKeyOptions
     *
     * @return FormView
     */
    public function getTestFormView(
        $formName,
        $routeName,
        $data = null,
        array $options = [],
        array $cacheKeyOptions = []
    ) {
        $options = array_merge($this->getFormOptions($routeName), $options);

        $form = new FormStub($formName);

        return $this->getFormView($form, $data, $options, $cacheKeyOptions);
    }

    /**
     * @param string $formName
     * @param array  $formOptions
     * @param array  $cacheKeyOptions
     *
     * @return string
     */
    public function getTestCacheKey($formName, array $formOptions = [], array $cacheKeyOptions = [])
    {
        $form = new FormStub($formName);

        return $this->getCacheKey($form, $formOptions, $cacheKeyOptions);
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
