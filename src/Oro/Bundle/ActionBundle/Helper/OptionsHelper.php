<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\OptionsAssembler;

use Oro\Component\Action\Model\ContextAccessor;

class OptionsHelper
{
    /** @var ContextHelper */
    protected $contextHelper;

    /** @var ApplicationsHelper */
    protected $applicationsHelper;

    /** @var OptionsAssembler */
    protected $optionsAssembler;

    /** @var ContextAccessor */
    protected $contextAccessor;

    /** @var RouterInterface */
    protected $router;

    /**
     * @param ContextHelper $contextHelper
     * @param ApplicationsHelper $applicationsHelper
     * @param OptionsAssembler $optionsAssembler
     * @param ContextAccessor $contextAccessor
     * @param RouterInterface $router
     */
    public function __construct(
        ContextHelper $contextHelper,
        ApplicationsHelper $applicationsHelper,
        OptionsAssembler $optionsAssembler,
        ContextAccessor $contextAccessor,
        RouterInterface $router
    ) {
        $this->contextHelper = $contextHelper;
        $this->applicationsHelper = $applicationsHelper;
        $this->optionsAssembler = $optionsAssembler;
        $this->contextAccessor = $contextAccessor;
        $this->router = $router;
    }

    /**
     * @param Operation $operation
     * @param array $context
     * @return array
     */
    public function getFrontendOptions(Operation $operation, array $context = null)
    {
        $actionContext = $this->contextHelper->getContext($context);
        $actionData = $this->contextHelper->getActionData($actionContext);

        $frontendOptions = $this->resolveOptions(
            $actionData,
            $operation->getDefinition()->getFrontendOptions()
        );

        $buttonOptions = $this->resolveOptions(
            $actionData,
            $operation->getDefinition()->getButtonOptions()
        );

        $actionName = $operation->getName();

        $executionUrl = $this->router->generate(
            $this->applicationsHelper->getExecutionRoute(),
            array_merge($actionContext, ['actionName' => $actionName])
        );

        $dialogUrl = $this->router->generate(
            $this->applicationsHelper->getDialogRoute(),
            array_merge($actionContext, ['actionName' => $actionName])
        );

        $options = [
            'hasDialog' => $operation->hasForm(),
            'showDialog' => !empty($frontendOptions['show_dialog']),
            'dialogOptions' => [
                'title' => $operation->getDefinition()->getLabel(),
                'dialogOptions' => !empty($frontendOptions['options']) ? $frontendOptions['options'] : []
            ],
            'executionUrl' => $executionUrl,
            'dialogUrl' => $dialogUrl,
            'url' => $operation->hasForm() ? $dialogUrl : $executionUrl,
        ];

        $data = [];

        $this->addOption($options, $frontendOptions, 'confirmation');
        $this->addOption($data, $buttonOptions, 'page_component_module');
        $this->addOption($data, $buttonOptions, 'page_component_options');

        if (!empty($buttonOptions['data'])) {
            $data = array_merge($data, $buttonOptions['data']);
        }

        return ['options' => $options, 'data' => $data];
    }

    /**
     * @param ActionData $data
     * @param array $options
     * @return array
     */
    protected function resolveOptions(ActionData $data, array $options)
    {
        return $this->resolveValues($data, $this->optionsAssembler->assemble($options));
    }

    /**
     * @param ActionData $data
     * @param array $options
     * @return array
     */
    protected function resolveValues(ActionData $data, array $options)
    {
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                $options[$key] = $this->resolveValues($data, $value);
            } else {
                $options[$key] = $this->contextAccessor->getValue($data, $value);
            }
        }

        return $options;
    }

    /**
     * @param aray $options
     * @param array $source
     * @param string $sourceKey
     */
    protected function addOption(array &$options, array $source, $sourceKey)
    {
        $optionsKey = str_replace('_', '-', $sourceKey);

        if (!empty($source[$sourceKey])) {
            $options[$optionsKey] = $source[$sourceKey];
        }
    }
}
