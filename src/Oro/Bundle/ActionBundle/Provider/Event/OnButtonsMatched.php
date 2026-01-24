<?php

namespace Oro\Bundle\ActionBundle\Provider\Event;

use Oro\Bundle\ActionBundle\Button\ButtonsCollection;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when buttons have been matched and collected by the button provider.
 *
 * This event allows listeners to inspect and modify the collection of matched buttons
 * before they are returned to the caller.
 */
class OnButtonsMatched extends Event
{
    const NAME = 'oro_action.button_provider.on_buttons_matched';

    /** @var ButtonsCollection */
    protected $buttons;

    public function __construct(ButtonsCollection $buttons)
    {
        $this->buttons = $buttons;
    }

    /**
     * @return ButtonsCollection
     */
    public function getButtons()
    {
        return $this->buttons;
    }
}
