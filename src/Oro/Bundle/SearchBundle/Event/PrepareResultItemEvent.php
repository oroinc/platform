<?php

namespace Oro\Bundle\SearchBundle\Event;

use Oro\Bundle\SearchBundle\Query\Result\Item;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when preparing a search result item for display.
 *
 * This event allows listeners to modify or enhance search result items before
 * they are displayed to the user. Listeners can access the search result item
 * and optionally the corresponding entity object, enabling customization of
 * result presentation and data enrichment.
 */
class PrepareResultItemEvent extends Event
{
    /**
     * Event name
     * @const string
     */
    const EVENT_NAME = 'oro_search.prepare_result_item';

    /**
     * @var \Oro\Bundle\SearchBundle\Query\Result\Item
     */
    protected $resultItem;

    /**
     * @var Object
     */
    protected $entityObject;

    public function __construct(Item $item, $entityObject = null)
    {
        $this->resultItem = $item;
        $this->entityObject = $entityObject;
    }

    /**
     * Getter for result item
     *
     * @return Item
     */
    public function getResultItem()
    {
        return $this->resultItem;
    }

    /**
     * Getter for entity object
     *
     * @return Object
     */
    public function getEntity()
    {
        return $this->entityObject;
    }
}
