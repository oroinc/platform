<?php

namespace Oro\Bundle\FormBundle\Model;

use Oro\Bundle\FormBundle\Form\Handler\CallbackFormHandler;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;

/**
 * Creates {@see Update} according to form, form handler, data and form template data provider
 * using {@see UpdateBuilder}
 */
class UpdateFactory
{
    private FormFactory $formFactory;

    private FormHandlerRegistry $formHandlerRegistry;

    private FormTemplateDataProviderResolver $formTemplateDataProviderResolver;

    public function __construct(
        FormFactory $formFactory,
        FormHandlerRegistry $formHandlerRegistry,
        FormTemplateDataProviderResolver $formTemplateDataProviderResolver
    ) {
        $this->formFactory = $formFactory;
        $this->formHandlerRegistry = $formHandlerRegistry;
        $this->formTemplateDataProviderResolver = $formTemplateDataProviderResolver;
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
        return $this->createBuilder()
            ->build(
                $data,
                $this->resolveForm($form, $data),
                $this->resolveHandler($formHandler),
                $this->formTemplateDataProviderResolver->resolve($resultProvider)
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
