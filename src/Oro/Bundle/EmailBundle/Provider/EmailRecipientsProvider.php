<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EmailBundle\Event\EmailRecipientsLoadEvent;

class EmailRecipientsProvider
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string|null $query
     * @param int $limit
     *
     * @return array
     */
    public function getEmailRecipients($query = null, $limit = 100)
    {
        if (!$this->dispatcher->hasListeners(EmailRecipientsLoadEvent::NAME)) {
            return [];
        }
        
        $event = new EmailRecipientsLoadEvent($query, $limit);
        $this->dispatcher->dispatch(EmailRecipientsLoadEvent::NAME, $event);

        return $event->getResults();
    }
}
