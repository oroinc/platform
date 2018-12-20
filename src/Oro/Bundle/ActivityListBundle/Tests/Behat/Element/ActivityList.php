<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class ActivityList extends Element
{
    /**
     * @param string $content
     * @return ActivityListItem
     */
    public function getActivityListItem($content)
    {
        $item = $this->elementFactory->findElementContains('ActivityListItem', $content);
        self::assertTrue($item->isValid(), sprintf('Item with "%s" content not found in activity list', $content));

        return $item;
    }

    /**
     * @return ActivityListItem
     */
    public function getCollapsedItem()
    {
        $item = $this->spin(function (ActivityList $activityList) {
            return $activityList->find('css', 'div.accordion-body.show');
        }, 3);
        self::assertNotNull($item, 'Not found collapsed items in activity list');

        return $this->elementFactory->wrapElement('ActivityListItem', $item);
    }

    /**
     * @return ActivityListItem[]
     */
    public function getItems()
    {
        $itemElements = $this->findAll('css', 'div.list-item');

        return array_map(function (NodeElement $element) {
            return $this->elementFactory->wrapElement('ActivityListItem', $element);
        }, $itemElements);
    }
}
