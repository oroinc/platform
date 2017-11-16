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
        $frontendOptions = $this->operation->getDefinition()->getFrontendOptions();

        return array_merge(
            $defaultData,
            $customData,
            [
                'params' => $this->operation->getDefinition(),
                'actionData' => $this->data,
                'frontendOptions' => $frontendOptions,
                'buttonOptions' => $this->operation->getDefinition()->getButtonOptions(),
                'hasForm' => $this->operation->hasForm(),
                'showDialog' => !empty($frontendOptions['show_dialog']),
                'routeParams' => [
                    'operationName' => $this->operation->getName(),
                    'entityClass' => $this->buttonContext->getEntityClass(),
                    'entityId' => $this->buttonContext->getEntityId(),
                    'route' => $this->buttonContext->getRouteName(),
                    'datagrid' => $this->buttonContext->getDatagridName(),
                    'group' => $this->buttonContext->getGroup(),
                ],
                'executionRoute' => $this->buttonContext->getExecutionRoute(),
                'dialogRoute' => $this->buttonContext->getFormDialogRoute(),
                'additionalData' => $this->getDatagridData(),
                'jsDialogWidget' => self::DEFAULT_JS_DIALOG_WIDGET,
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
     * {@inheritdoc}
     */
    public function getGroup()
    {
        $buttonOptions = $this->operation->getDefinition()->getButtonOptions();

        return isset($buttonOptions['group']) ? $buttonOptions['group'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslationDomain()
    {
        return null;
    }

    /**
     * @return Operation
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param ActionData $data
     * @return $this
     */
    public function setData(ActionData $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return ActionData
     */
    public function getData()
    {
        return $this->data;
    }

    public function __clone()
    {
        $this->buttonContext = clone $this->getButtonContext();
        $this->data = clone $this->data;
        $this->operation = clone $this->getOperation();
    }
}
