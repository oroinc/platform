<?php

namespace Oro\Bundle\FormBundle\Model;

use Oro\Bundle\FormBundle\Form\Handler\CallbackFormHandler;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Provider\CallbackFormTemplateDataProvider;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;

class UpdateFactory
{
    /** @var FormFactory */
    private $formFactory;

    /** @var FormHandlerRegistry */
    private $formHandlerRegistry;

    /** @var FormTemplateDataProviderRegistry */
    private $dataProviderRegistry;

    /**
     * @param FormFactory $formFactory
     * @param FormHandlerRegistry $formHandlerRegistry
     * @param FormTemplateDataProviderRegistry $dataProviderRegistry
     */
    public function __construct(
        FormFactory $formFactory,
        FormHandlerRegistry $formHandlerRegistry,
        FormTemplateDataProviderRegistry $dataProviderRegistry
    ) {
        $this->formFactory = $formFactory;
        $this->formHandlerRegistry = $formHandlerRegistry;
        $this->dataProviderRegistry = $dataProviderRegistry;
    }

    /**
     * @param object $data
     * @param string|FormInterface $form
     * @param null|callable|string|FormHandlerInterface $formHandler
     * @param null|callable|string|FormTemplateDataProviderInterface $resultProvider
     * @return UpdateInterface
     */
    public function createUpdate($data, $form, $formHandler, $resultProvider)
    {
        $builder = $this->createBuilder();
        return $builder->build(
            $data,
            $this->resolveForm($form, $data),
            $this->resolveHandler($formHandler),
            $this->resolveTemplateDataProvider($resultProvider)
        );
    }

    /**
     * @return UpdateBuilder
     */
    protected function createBuilder()
    {
        return new UpdateBuilder();
    }

    /**
     * @param null|callable|string|FormHandlerInterface $formHandler
     *
     * @return FormHandlerInterface
     */
    private function resolveHandler($formHandler = null)
    {
        $formHandler = $formHandler ?: FormHandlerRegistry::DEFAULT_HANDLER_NAME;

        if ($formHandler instanceof FormHandlerInterface) {
            return $formHandler;
        }

        if (is_callable($formHandler)) {
            return new CallbackFormHandler($formHandler);
        }

        return $this->formHandlerRegistry->get($formHandler);
    }

    /**
     * @param null|callable|string|FormTemplateDataProviderInterface $resultProvider
     *
     * @return FormTemplateDataProviderInterface
     */
    private function resolveTemplateDataProvider($resultProvider = null)
    {
        $resultProvider = $resultProvider ?: FormTemplateDataProviderRegistry::DEFAULT_PROVIDER_NAME;

        if ($resultProvider instanceof FormTemplateDataProviderInterface) {
            return $resultProvider;
        }

        if (is_callable($resultProvider)) {
            return new CallbackFormTemplateDataProvider($resultProvider);
        }

        return $this->dataProviderRegistry->get($resultProvider);
    }

    /**
     * @param string|FormInterface $form
     * @param object $data
     * @return FormInterface
     */
    private function resolveForm($form, $data)
    {
        if ($form instanceof FormInterface) {
            return $form;
        }

        return $this->formFactory->create($form, $data);
    }
}
