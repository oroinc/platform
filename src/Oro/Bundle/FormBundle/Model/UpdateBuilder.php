<?php

namespace Oro\Bundle\FormBundle\Model;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\FormBundle\Form\Handler\CallbackFormHandler;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Provider\CallbackFormTemplateDataProvider;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;

class UpdateBuilder
{
    /** @var FormHandlerRegistry */
    private $formHandlerRegistry;

    /** @var FormTemplateDataProviderRegistry */
    private $dataProviderRegistry;

    /**
     * @param FormHandlerRegistry $formHandlerRegistry
     * @param FormTemplateDataProviderRegistry $dataProviderRegistry
     */
    public function __construct(
        FormHandlerRegistry $formHandlerRegistry,
        FormTemplateDataProviderRegistry $dataProviderRegistry
    ) {
        $this->formHandlerRegistry = $formHandlerRegistry;
        $this->dataProviderRegistry = $dataProviderRegistry;
    }

    /**
     * @param object $data
     * @param FormInterface $form
     * @param string $saveMessage
     * @param null|callable|string|FormHandlerInterface $formHandler
     * @param null|callable|string|FormTemplateDataProviderInterface $resultProvider
     *
     * @return Update
     */
    public function create(
        $data,
        FormInterface $form,
        $saveMessage,
        $formHandler = null,
        $resultProvider = null
    ) {
        $update = new Update();
        $update->data = $data;
        $update->form = $form;
        $update->saveMessage = $saveMessage;
        $update->handler = $this->createHandler($formHandler);
        $update->resultDataProvider = $this->createResultProvider($resultProvider);

        return $update;
    }

    /**
     * @param null|callable|string|FormHandlerInterface $formHandler
     *
     * @return \Closure|null|string
     */
    private function createHandler($formHandler = null)
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
    private function createResultProvider($resultProvider = null)
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
}
