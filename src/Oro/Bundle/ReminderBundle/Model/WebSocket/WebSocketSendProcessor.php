<?php

namespace Oro\Bundle\ReminderBundle\Model\WebSocket;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\SendProcessorInterface;
use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;

class WebSocketSendProcessor implements SendProcessorInterface
{
    const NAME = 'web_socket';

    /**
     * @var TopicPublisher
     */
    protected $topicPublisher;

    /**
     * @var MessageParamsProvider
     */
    protected $messageParamsProvider;

    public function __construct(
        TopicPublisher $topicPublisher,
        MessageParamsProvider $messageParamsProvider
    ) {
        $this->topicPublisher = $topicPublisher;
        $this->messageParamsProvider = $messageParamsProvider;
    }

    /**
     * Send reminder using WebSocket
     *
     * @param Reminder $reminder
     * @return string
     */
    public function process(Reminder $reminder)
    {
        $message = $this->messageParamsProvider->getMessageParams($reminder);

        $sentResult = $this->sendMessage($reminder, $message);

        $reminder->setState($sentResult ? Reminder::STATE_IN_PROGRESS : Reminder::STATE_NOT_SENT);
    }

    /**
     * @param Reminder $reminder
     * @param string   $message
     * @return bool
     */
    protected function sendMessage(Reminder $reminder, $message)
    {
        return $this->topicPublisher->send(
            sprintf(
                'oro/reminder/remind_user_%s',
                $reminder->getRecipient()
                    ->getId()
            ),
            json_encode($message)
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
