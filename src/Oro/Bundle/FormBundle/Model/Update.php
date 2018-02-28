<?php

namespace Oro\Bundle\FormBundle\Model;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class Update implements UpdateInterface
{
    /** @var FormInterface */
    private $form;

    /** @var object */
    private $formData;

    /** @var FormTemplateDataProviderInterface */
    private $templateDataProvider;

    /** @var FormHandlerInterface */
    private $handler;

    /**
     * @param object $data
     *
     * @return $this
     */
    public function setFormData($data)
    {
        $this->formData = $data;

        return $this;
    }

    /**
     * @param FormInterface $form
     *
     * @return $this
     */
    public function setFrom(FormInterface $form)
    {
        $this->form = $form;

        return $this;
    }

    /**
     * @param FormHandlerInterface $formHandler
     *
     * @return $this
     */
    public function setHandler(FormHandlerInterface $formHandler)
    {
        $this->handler = $formHandler;

        return $this;
    }

    /**
     * @param FormTemplateDataProviderInterface $templateDataProvider
     *
     * @return $this
     */
    public function setTemplateDataProvider(FormTemplateDataProviderInterface $templateDataProvider)
    {
        $this->templateDataProvider = $templateDataProvider;

        return $this;
    }

    /** {@inheritdoc} */
    public function handle(Request $request)
    {
        return $this->handler->process($this->formData, $this->form, $request);
    }

    /** {@inheritdoc} */
    public function getTemplateData(Request $request)
    {
        return $this->templateDataProvider->getData($this->formData, $this->form, $request);
    }

    /** {@inheritdoc} */
    public function getForm()
    {
        return $this->form;
    }

    /** {@inheritdoc} */
    public function getFormData()
    {
        return $this->formData;
    }
}
