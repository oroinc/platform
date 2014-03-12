<?php

namespace Oro\Bundle\ReminderBundle\Model\WebSocket;

use Symfony\Component\Translation\Translator;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\UrlProvider;

class MessageParamsProvider
{
    /**
     * @var DateTimeFormatter
     */
    protected $dateTimeFormatter;
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var UrlProvider
     */
    protected $urlProvider;

    /**
     * @param Translator        $translator
     * @param DateTimeFormatter $dateTimeFormatter
     * @param UrlProvider       $urlProvider
     */
    public function __construct(Translator $translator, DateTimeFormatter $dateTimeFormatter, UrlProvider $urlProvider)
    {
        $this->dateTimeFormatter = $dateTimeFormatter;
        $this->translator = $translator;
        $this->urlProvider = $urlProvider;
    }

    /**
     * @param Reminder $reminder
     * @return array
     */
    public function getMessageParams(Reminder $reminder)
    {
        $translationParams = array(
            '%expireAt%'   => $this->dateTimeFormatter->format($reminder->getExpireAt()),
            '%subject%' => $reminder->getSubject()
        );

        $message = $this->translator->trans('oro.reminder.message', $translationParams);

        return array('text' => $message, 'url' => $this->urlProvider->getUrl($reminder), 'id' => $reminder->getId());
    }
}
