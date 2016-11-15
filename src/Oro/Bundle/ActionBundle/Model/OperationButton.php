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
        return static::DEFAULT_TEMPLATE;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateData()
    {
        $definition = $this->operation->getDefinition();
        $buttonOptions = $definition->getButtonOptions();

        if (in_array(
            $this->getButtonContext()->getGroup(),
            [
                null,
                OperationRegistry::DEFAULT_GROUP,
                OperationRegistry::UPDATE_PAGE_GROUP,
                OperationRegistry::VIEW_PAGE_GROUP,
            ],
            true
        )) {
            if (!isset($buttonOptions['aCss'])) {
                $buttonOptions['aCss'] = '';
            }

            $buttonOptions['aCss'] .= 'btn';
        }

        $definition->setButtonOptions($buttonOptions);

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
