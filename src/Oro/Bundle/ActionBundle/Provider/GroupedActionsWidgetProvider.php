<?php

namespace Oro\Bundle\ActionBundle\Provider;

use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\UIBundle\Provider\WidgetProviderInterface;

/**
 * Provides widgets for actions that are grouped in a specific group.
 */
class GroupedActionsWidgetProvider implements WidgetProviderInterface
{
    private const BUTTON_TEMPLATE = '@OroAction/Widget/grouped_action_button.html.twig';
    private const LINK_TEMPLATE = '@OroAction/Widget/grouped_action_link.html.twig';

    private string $buttonGroup;
    private string $actionGroup;
    private ContextHelper $contextHelper;
    private ButtonSearchContextProvider $buttonSearchContextProvider;
    private ButtonProvider $buttonProvider;

    public function __construct(
        string $buttonGroup,
        string $actionGroup,
        ContextHelper $contextHelper,
        ButtonSearchContextProvider $buttonSearchContextProvider,
        ButtonProvider $buttonProvider
    ) {
        $this->buttonGroup = $buttonGroup;
        $this->actionGroup = $actionGroup;
        $this->contextHelper = $contextHelper;
        $this->buttonSearchContextProvider = $buttonSearchContextProvider;
        $this->buttonProvider = $buttonProvider;
    }

    #[\Override]
    public function supports($object)
    {
        return $this->buttonProvider->hasButtons($this->getButtonSearchContext($object));
    }

    #[\Override]
    public function getWidgets($object)
    {
        $result = [];
        $buttons = $this->buttonProvider->findAvailable($this->getButtonSearchContext($object));
        foreach ($buttons as $button) {
            $result[] = [
                'name'   => $button->getName(),
                'button' => ['template' => self::BUTTON_TEMPLATE, 'data' => $button],
                'link'   => ['template' => self::LINK_TEMPLATE],
                'group'  => $this->buttonGroup
            ];
        }

        return $result;
    }

    private function getButtonSearchContext($object): ButtonSearchContext
    {
        $context = $this->contextHelper->getActionParameters(['entity' => $object]);
        $context['group'] = $this->actionGroup;

        return $this->buttonSearchContextProvider->getButtonSearchContext($context);
    }
}
