<?php

namespace Oro\Bundle\ActionBundle\Model;

class OperationButton implements ButtonInterface
{
    const DEFAULT_TEMPLATE = 'OroActionBundle:Operation:button.html.twig';
    const FRONTEND_TEMPLATE_KEY = 'template';

    /**
     * @var Operation
     */
    protected $operation;

    /**
     * @var ButtonContext
     */
    protected $buttonContext;

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
        $frontendOptions = $this->operation->getDefinition()->getFrontendOptions();

        if (!empty($frontendOptions[static::FRONTEND_TEMPLATE_KEY])) {
            return $frontendOptions[static::FRONTEND_TEMPLATE_KEY];
        }

        return static::DEFAULT_TEMPLATE;
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
