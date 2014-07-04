<?php

namespace Oro\Bundle\UIBundle\Provider;

use Doctrine\Common\Util\ClassUtils;

/**
 * This provider calls all registered leaf providers in a chain, merges and does grouping of widgets returned
 * by each leaf provider and orders result widgets by priority.
 */
class GroupingChainWidgetProvider extends ChainWidgetProvider
{
    /** @var LabelProviderInterface */
    protected $groupNameProvider;

    /**
     * @param LabelProviderInterface $groupNameProvider
     */
    public function __construct(LabelProviderInterface $groupNameProvider = null)
    {
        $this->groupNameProvider = $groupNameProvider;
    }

    /**
     * {@inheritdoc}
     *
     * The format of returning array:
     *      [group name] =>
     *          'widgets' => array
     */
    public function getWidgets($entity)
    {
        $widgets = parent::getWidgets($entity);

        $result = [];
        foreach ($widgets as $widget) {
            if (isset($widget['group'])) {
                $groupName = $widget['group'];
                unset($widget['group']);
            } else {
                $groupName = '';
            }
            if (!isset($result[$groupName])) {
                $result[$groupName] = [
                    'widgets' => []
                ];
                if ($this->groupNameProvider && !empty($groupName)) {
                    $result[$groupName]['label'] = $this->groupNameProvider->getLabel(
                        [
                            'groupName'   => $groupName,
                            'entityClass' => ClassUtils::getClass($entity)
                        ]
                    );
                }
            }

            $result[$groupName]['widgets'][] = $widget;
        }

        return $result;
    }
}
