<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
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
    protected $translator;

    /** @var ApplicationsUrlHelper */
    protected $applicationsUrlHelper;

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
     * @param Operation $operation
     * @param array $context
     * @return array
     */
    public function getFrontendOptions(Operation $operation, array $context = null)
    {
        $actionContext = $this->contextHelper->getContext($context);
        $actionData = $this->contextHelper->getActionData($actionContext);

        return [
            'options' => $this->createOptions($operation, $actionData, $actionContext),
            'data' => $this->createData($operation, $actionData)
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
        foreach ($options as &$value) {
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
     * @param Operation $operation
     * @param ActionData $actionData
     * @param array $actionContext
     * @return array
     */
    protected function createOptions(Operation $operation, ActionData $actionData, array $actionContext)
    {
        $actionContext = array_merge($actionContext, ['operationName' => $operation->getName()]);

        $executionUrl = $this->applicationsUrlHelper->getExecutionUrl($actionContext);
        $dialogUrl = $this->applicationsUrlHelper->getDialogUrl($actionContext);

        $frontendOptions = $this->resolveOptions($actionData, $operation->getDefinition()->getFrontendOptions());

        $title = isset($frontendOptions['title']) ? $frontendOptions['title'] : $operation->getDefinition()->getLabel();

        $options = [
            'hasDialog' => $operation->hasForm(),
            'showDialog' => !empty($frontendOptions['show_dialog']),
            'dialogOptions' => [
                'title' => $this->translator->trans($title),
                'dialogOptions' => !empty($frontendOptions['options']) ? $frontendOptions['options'] : []
            ],
            'executionUrl' => $executionUrl,
            'dialogUrl' => $dialogUrl,
            'url' => $operation->hasForm() ? $dialogUrl : $executionUrl,
        ];

        $this->addOption($options, $frontendOptions, 'confirmation');

        return $options;
    }

    /**
     * @param Operation $operation
     * @param ActionData $actionData
     * @return array
     */
    protected function createData(Operation $operation, ActionData $actionData)
    {
        $buttonOptions = $this->resolveOptions(
            $actionData,
            $operation->getDefinition()->getButtonOptions()
        );

        $data = [];
        $this->addOption($data, $buttonOptions, 'page_component_module');
        $this->addOption($data, $buttonOptions, 'page_component_options');

        if (!empty($buttonOptions['data'])) {
            $data = array_merge($data, $buttonOptions['data']);
        }

        return $data;
    }
}
