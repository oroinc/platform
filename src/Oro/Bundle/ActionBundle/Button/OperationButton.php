<?php

namespace Oro\Bundle\ActionBundle\Button;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;

class OperationButton implements ButtonInterface
{
    const DEFAULT_TEMPLATE = 'OroActionBundle:Operation:button.html.twig';
    const BUTTON_TEMPLATE_KEY = 'template';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Operation
     */
    protected $operation;

    /**
     * @var ButtonContext
     */
    protected $buttonContext;

    /**
     * @var ActionData
     */
    protected $data;

    /**
     * @param string $name Name of origin operation
     * @param Operation $operation
     * @param ButtonContext $buttonContext
     * @param ActionData $data
     */
    public function __construct($name, Operation $operation, ButtonContext $buttonContext, ActionData $data)
    {
        $this->name = $name;
        $this->operation = $operation;
        $this->buttonContext = $buttonContext;
        $this->data = $data;
    }

    /**
     * Gets origin operation name
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->operation->getDefinition()->getLabel();
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        $buttonOptions = $this->operation->getDefinition()->getButtonOptions();

        return isset($buttonOptions['icon']) ? $buttonOptions['icon'] : null;
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
    public function getTemplateData(array $customData = [])
    {
        $defaultData = [
            'aClass' => ''
        ];

        return array_merge(
            $defaultData,
            $customData,
            [
                'operation' => $this->operation,
                'params' => $this->operation->getDefinition(),
                'actionData' => $this->data,
                'buttonContext' => $this->buttonContext,
                'additionalData' => $this->getDatagridData()
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    private function getDatagridData()
    {
        $datagridOptions = $this->operation->getDefinition()->getDatagridOptions();

        return isset($datagridOptions['data']) ? $datagridOptions['data'] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonContext()
    {
        return $this->buttonContext;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        $buttonOptions = $this->operation->getDefinition()->getButtonOptions();

        return isset($buttonOptions['group']) ? $buttonOptions['group'] : null;
    }

    /**
     * @return Operation
     */
    public function getOperation()
    {
        return $this->operation;
    }
}
