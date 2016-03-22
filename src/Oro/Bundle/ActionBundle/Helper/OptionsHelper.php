<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\OptionsAssembler;

use Oro\Component\Action\Model\ContextAccessor;

class OptionsHelper
{
    /** @var ContextHelper */
    protected $contextHelper;

    /** @var OptionsAssembler */
    protected $optionsAssembler;

    /** @var ContextAccessor */
    protected $contextAccessor;

    /** @var TranslatorInterface */
    private $translator;

    /** @var ApplicationsUrlHelper */
    private $applicationsUrlHelper;

    /**
     * @param ContextHelper $contextHelper
     * @param OptionsAssembler $optionsAssembler
     * @param ContextAccessor $contextAccessor
     * @param ApplicationsUrlHelper $applicationsUrlHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ContextHelper $contextHelper,
        OptionsAssembler $optionsAssembler,
        ContextAccessor $contextAccessor,
        ApplicationsUrlHelper $applicationsUrlHelper,
        TranslatorInterface $translator
    ) {
        $this->contextHelper = $contextHelper;
        $this->optionsAssembler = $optionsAssembler;
        $this->contextAccessor = $contextAccessor;
        $this->applicationsUrlHelper = $applicationsUrlHelper;
        $this->translator = $translator;
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

        return [
            'options' => $this->createOptions($action, $actionData, $actionContext),
            'data' => $this->createData($action, $actionData)
        ];
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
        foreach ($options as $key => &$value) {
            if (is_array($value)) {
                $value = $this->resolveValues($data, $value);
            } else {
                $value = $this->contextAccessor->getValue($data, $value);
            }
        }

        return $options;
    }

    /**
     * @param array $options
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

    /**
     * @param Action $action
     * @param ActionData $actionData
     * @param array $actionContext
     * @return array
     */
    protected function createOptions(Action $action, ActionData $actionData, array $actionContext)
    {
        $actionName = $action->getName();

        $frontendOptions = $this->resolveOptions(
            $actionData,
            $action->getDefinition()->getFrontendOptions()
        );

        $executionUrl = $this->applicationsUrlHelper->getExecutionUrl(
            array_merge($actionContext, ['actionName' => $actionName])
        );

        $dialogUrl = $this->applicationsUrlHelper->getDialogUrl(
            array_merge($actionContext, ['actionName' => $actionName])
        );

        $label = $action->getDefinition()->getLabel();

        $options = [
            'hasDialog' => $action->hasForm(),
            'showDialog' => !empty($frontendOptions['show_dialog']),
            'dialogOptions' => [
                'title' => $this->translator->trans($label) ?: $label,
                'dialogOptions' => !empty($frontendOptions['options']) ? $frontendOptions['options'] : []
            ],
            'executionUrl' => $executionUrl,
            'dialogUrl' => $dialogUrl,
            'url' => $action->hasForm() ? $dialogUrl : $executionUrl,
        ];

        $this->addOption($options, $frontendOptions, 'confirmation');

        return $options;
    }

    /**
     * @param Action $action
     * @param ActionData $actionData
     * @return array
     */
    protected function createData(Action $action, ActionData $actionData)
    {
        $buttonOptions = $this->resolveOptions(
            $actionData,
            $action->getDefinition()->getButtonOptions()
        );

        $data = [];
        $this->addOption($data, $buttonOptions, 'page_component_module');
        $this->addOption($data, $buttonOptions, 'page_component_options');

        if (!empty($buttonOptions['data'])) {
            $data = array_merge($data, $buttonOptions['data']);

            return $data;
        }

        return $data;
    }
}
