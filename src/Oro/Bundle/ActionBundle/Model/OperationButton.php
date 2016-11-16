<?php

namespace Oro\Bundle\ActionBundle\Model;

class OperationButton implements ButtonInterface
{
    const DEFAULT_TEMPLATE = 'OroActionBundle:Operation:button.html.twig';
    const BUTTON_TEMPLATE_KEY = 'template';

    /**
     * @var Operation
     */
    protected $operation;

    /**
     * @var ButtonContext
     */
    protected $buttonContext;

    /**
     * @param Operation $operation
     * @param ButtonContext $buttonContext
     */
    public function __construct(Operation $operation, ButtonContext $buttonContext)
    {
        $this->operation = $operation;
        $this->buttonContext = $buttonContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return $this->operation->getDefinition()->getOrder();
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        $buttonOptions = $this->operation->getDefinition()->getButtonOptions();

        return !empty($buttonOptions[static::BUTTON_TEMPLATE_KEY])
            ? $buttonOptions[static::BUTTON_TEMPLATE_KEY]
            : static::DEFAULT_TEMPLATE;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateData()
    {
        return [
            'operation' => $this->operation,
            'params' => $this->operation->getDefinition(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonContext()
    {
        return $this->buttonContext;
    }
}
