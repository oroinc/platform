<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

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
    public function getTemplateData(array $customData = [])
    {
        $defaultData = [
            'aClass' => ''
        ];

        return array_merge($defaultData, $customData, [
            'operation' => $this->operation,
            'params' => $this->operation->getDefinition(),
            'buttonContext' => $this->buttonContext,
        ]);
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
     * @return array
     */
    public function getAttributesData()
    {
        $definition = $this->operation->getDefinition();

        $frontendOptions = $definition->getFrontendOptions();
        $buttonOptions = $definition->getButtonOptions();
        if (!empty($frontendOptions['title'])) {
            $title = $frontendOptions['title'];
        } else {
            $title = $definition->getLabel();
        }
        $icon = !empty($buttonOptions['icon']) ? $buttonOptions['icon'] : '';

        return [
            'name' => $definition->getName(),
            'label' => $definition->getLabel(),
            'title' => $title,
            'icon' => $icon,
            'action' => $this->operation,
            'trans_domain' => TranslationManager::DEFAULT_DOMAIN,
        ];
    }
}
