<?php

namespace Oro\Bundle\ReminderBundle\Model\WebSocket;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\SendProcessorInterface;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;

/**
 * Sends messages about reminders to websocket server.
 */
class WebSocketSendProcessor implements SendProcessorInterface
{
    const NAME = 'web_socket';

    /**
     * @var array
     */
    protected $remindersByRecipient = array();

    /**
     * @var WebsocketClientInterface
     */
    protected $websocketClient;

    /**
     * @var ConnectionChecker
     */
    protected $connectionChecker;

    /**
     * @var MessageParamsProvider
     */
    protected $messageParamsProvider;

    /**
     * @param WebsocketClientInterface $websocketClient
     * @param ConnectionChecker $connectionChecker
     * @param MessageParamsProvider $messageParamsProvider
     */
    public function __construct(
        WebsocketClientInterface $websocketClient,
        ConnectionChecker $connectionChecker,
        MessageParamsProvider $messageParamsProvider
    ) {
        $this->websocketClient = $websocketClient;
        $this->connectionChecker = $connectionChecker;
        $this->messageParamsProvider = $messageParamsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function push(Reminder $reminder)
    {
        $recipientId = $reminder->getRecipient()->getId();
        if (!isset($this->remindersByRecipient[$recipientId])) {
            $this->remindersByRecipient[$recipientId] = array();
        }
        $this->remindersByRecipient[$recipientId][] = $reminder;
    }

    /**
     * Send reminders using WebSocket
     */
    public function process()
    {
        foreach ($this->remindersByRecipient as $recipientId => $reminders) {
            $this->processRecipientReminders($reminders, $recipientId);
        }

        $this->remindersByRecipient = array();
    }

    /**
     * Send group of reminders to recipient
     *
     * @param Reminder[] $reminders
     * @param integer $recipientId
     */
    protected function processRecipientReminders(array $reminders, $recipientId)
    {
        $messageData = array();

        // Collect message data

        foreach ($reminders as $reminder) {
            $messageData[] = $this->messageParamsProvider->getMessageParams($reminder);
        }

        // Send message
        try {
            $this->sendMessage($messageData, $recipientId);

            // Change state
            foreach ($reminders as $reminder) {
                $reminder->setState(Reminder::STATE_REQUESTED);
            }
        } catch (\Exception $exception) {
            foreach ($reminders as $reminder) {
                $reminder->setState(Reminder::STATE_FAIL);
                $reminder->setFailureException($exception);
            }
        }
    }

    /**
     * @param array $messageData
     * @param int $recipientId
     * @return bool
     */
    protected function sendMessage(array $messageData, $recipientId)
    {
        if (!$this->connectionChecker->checkConnection()) {
            return false;
        }

        return $this->websocketClient->publish(sprintf('oro/reminder_remind/%s', $recipientId), $messageData);
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
