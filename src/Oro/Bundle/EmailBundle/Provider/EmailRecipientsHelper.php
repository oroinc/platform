<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EmailBundle\Event\EmailRecipientsLoadEvent;

class EmailRecipientsHelper
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param EmailRecipientsLoadEvent $event
     * @param array $emails
     */
    public function addEmailsToContext(EmailRecipientsLoadEvent $event, array $emails)
    {
        $limit = $event->getRemainingLimit();
        $query = $event->getQuery();

        $excludedEmails = $event->getEmails();
        $filteredEmails = array_filter($emails, function ($email) use ($query, $excludedEmails) {
            return !in_array($email, $excludedEmails) && stripos($email, $query) !== false;
        });
        if (!$filteredEmails) {
            return;
        }

        $id = $this->translator->trans('oro.email.autocomplete.contexts');
        $resultsId = null;
        $results = $event->getResults();
        foreach ($results as $recordId => $record) {
            if ($record['text'] === $id) {
                $resultsId = $recordId;

                break;
            }
        }

        $children = $this->createResultFromEmails(array_splice($filteredEmails, 0, $limit));
        if ($resultsId !== null) {
            $results[$resultsId]['children'] = array_merge($results[$resultsId]['children'], $children);
        } else {
            $results = array_merge(
                $results,
                [
                    [
                        'text'     => $id,
                        'children' => $children,
                    ],
                ]
            );
        }

        $event->setResults($results);
    }

    /**
     * @param array $emails
     *
     * @return array
     */
    public function createResultFromEmails(array $emails)
    {
        $result = [];
        foreach ($emails as $email => $name) {
            $result[] = [
                'id'   => $email,
                'text' => $name,
            ];
        }

        return $result;
    }
}
