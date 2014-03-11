<?php

namespace Oro\Bundle\ReminderBundle\Model\WebSocket;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\Translator;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

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
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    public function __construct(
        Translator $translator,
        DateTimeFormatter $dateTimeFormatter,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->dateTimeFormatter = $dateTimeFormatter;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
    }

    public function getMessageParams(Reminder $reminder)
    {
        $translationParams = array(
            '%time%'   => $this->dateTimeFormatter->format($reminder->getExpireAt()),
            '%subject%' => $reminder->getSubject()
        );

        $message = $this->translator->trans('oro.reminder.message', $translationParams);

        return array('text' => $message, 'uri' => $this->getUrl($reminder), 'reminderId' => $reminder->getId());
    }

    /**
     * @param Reminder $reminder
     * @return string|null
     */
    protected function getUrl(Reminder $reminder)
    {
        if (!$reminder->getRelatedRouteName()) {
            return null;
        }

        return $this->urlGenerator->generate(
            $reminder->getRelatedRouteName(),
            $reminder->getRelatedRouteParameters()
        );
    }
}
