<?php

namespace Oro\Bundle\UIBundle\Provider;

use Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider;

class ActionButtonWidgetProvider implements WidgetProviderInterface
{
    /** @var PlaceholderProvider */
    protected $placeholderProvider;

    /** @var string */
    protected $buttonWidgetName;

    /** @var string */
    protected $linkWidgetName;

    /**
     * @param PlaceholderProvider $placeholderProvider
     * @param string              $buttonWidgetName
     * @param string              $linkWidgetName
     */
    public function __construct(
        PlaceholderProvider $placeholderProvider,
        $buttonWidgetName,
        $linkWidgetName
    ) {
        $this->placeholderProvider = $placeholderProvider;
        $this->buttonWidgetName    = $buttonWidgetName;
        $this->linkWidgetName      = $linkWidgetName;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgets($entity)
    {
        $result = [];

        $buttonWidget = $this->placeholderProvider->getItem($this->buttonWidgetName, ['entity' => $entity]);
        if ($buttonWidget) {
            $widget['name']   = $this->buttonWidgetName;
            $widget['button'] = $buttonWidget;
            if (!empty($this->linkWidgetName)) {
                $linkWidget = $this->placeholderProvider->getItem($this->linkWidgetName, ['entity' => $entity]);
                if ($linkWidget) {
                    $widget['link'] = $linkWidget;
                }
            }
            $result[] = $widget;
        }

        return $result;
    }
}
