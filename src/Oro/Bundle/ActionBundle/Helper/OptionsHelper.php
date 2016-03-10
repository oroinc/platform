<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\ActionBundle\Model\Action;
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
     * @param Action $action
     * @param array $context
     * @return array
     */
    public function getFrontendOptions(Action $action, array $context = null)
    {
        $actionContext = $this->contextHelper->getContext($context);
        $actionData = $this->contextHelper->getActionData($actionContext);

        $frontendOptions = $this->resolveOptions(
            $actionData,
            $action->getDefinition()->getFrontendOptions()
        );

        $buttonOptions = $this->resolveOptions(
            $actionData,
            $action->getDefinition()->getButtonOptions()
        );

        $actionName = $action->getName();

        $executionUrl = $this->router->generate(
            $this->applicationsHelper->getExecutionRoute(),
            array_merge($actionContext, ['actionName' => $actionName])
        );

        $dialogUrl = $this->router->generate(
            $this->applicationsHelper->getDialogRoute(),
            array_merge($actionContext, ['actionName' => $actionName])
        );

        $options = [
            'hasDialog' => $action->hasForm(),
            'showDialog' => !empty($frontendOptions['show_dialog']),
            'dialogOptions' => [
                'title' => $action->getDefinition()->getLabel(),
                'dialogOptions' => !empty($frontendOptions['options']) ? $frontendOptions['options'] : []
            ],
            'executionUrl' => $executionUrl,
            'dialogUrl' => $dialogUrl,
            'url' => $action->hasForm() ? $dialogUrl : $executionUrl,
        ];

        if (!empty($frontendOptions['confirmation'])) {
            $options['confirmation'] = $frontendOptions['confirmation'];
        }

        if (!empty($buttonOptions['page_component_module'])) {
            $options['pageComponentModule'] = $buttonOptions['page_component_module'];
        }

        if (!empty($buttonOptions['page_component_options'])) {
            $options['pageComponentOptions'] = $buttonOptions['page_component_options'];
        }

        if (!empty($buttonOptions['data'])) {
            $options = array_merge($options, $buttonOptions['data']);
        }

        return $options;
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
}
