<?php

namespace Oro\Bundle\ReminderBundle\Model\WebSocket;

use Symfony\Component\Translation\Translator;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\SendProcessorInterface;
use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

class WebSocketSendProcessor implements SendProcessorInterface
{
    const NAME = 'web_socket';

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var TopicPublisher
     */
    protected $topicPublisher;

    /**
     * @var DateTimeFormatter
     */
    protected $dateTimeFormatter;

    public function __construct(
        ConfigProvider $entityConfigProvider,
        Translator $translator,
        TopicPublisher $topicPublisher,
        DateTimeFormatter $dateTimeFormatter
    ) {
        $this->configProvider = $entityConfigProvider;
        $this->translator = $translator;
        $this->topicPublisher = $topicPublisher;
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    /**
     * Send reminder using WebSocket
     *
     * @param Reminder $reminder
     * @return string
     */
    public function process(Reminder $reminder)
    {
        $translationParams = array(
            '%time%'   => $this->dateTimeFormatter->format($reminder->getExpireAt()),
            '%subject%' => $reminder->getSubject()
        );
        $message = $this->translator->trans('oro.reminder.message', $translationParams);

        $sentResult = $this->sendMessage($reminder, $message);

        $reminder->setState($sentResult ? Reminder::STATE_SENT : Reminder::STATE_NOT_SENT);
    }

    /**
     * @param Reminder $reminder
     * @param string   $message
     * @return bool
     */
    protected function sendMessage(Reminder $reminder, $message)
    {
        return $this->topicPublisher->send(
            sprintf('oro/reminder/remind_user_%s', $reminder->getRecipient()->getId()),
            json_encode(array('text' => $message, 'uri' => '@todo replace with real url'))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.reminder.processor.web_socket.label';
    }
}
