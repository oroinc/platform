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
     * @param object|null $relatedEntity
     * @param string|null $query
     * @param int $limit
     *
     * @return array
     */
    public function getEmailRecipients($relatedEntity = null, $query = null, $limit = 100)
    {
        if (!$this->dispatcher->hasListeners(EmailRecipientsLoadEvent::NAME)) {
            return [];
        }

        $event = new EmailRecipientsLoadEvent($relatedEntity, $query, $limit);
        $this->dispatcher->dispatch(EmailRecipientsLoadEvent::NAME, $event);

        return $this->valuesAsKeys($event->getResults());
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function valuesAsKeys(array $data)
    {
        foreach ($data as $key => $record) {
            if (isset($record['children'])) {
                $data[$key]['children'] = $this->valuesAsKeys($record['children']);
            } else {
                $data[$key]['id'] = $record['text'];
            }
        }

        return $data;
    }
}
