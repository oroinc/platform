<?php
namespace Oro\Component\MessageQueue\Consumption\Translator;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\MessageProducerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class MessageTranslatorProcessor implements MessageProcessorInterface
{
    /**
     * @var MessageTranslatorInterface
     */
    protected $translator;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var string
     */
    protected $topicName;

    /**
     * @param SessionInterface $session
     * @param MessageTranslatorInterface $translator
     * @param string $topicName
     */
    public function __construct(SessionInterface $session, MessageTranslatorInterface $translator, $topicName)
    {
        $this->topicName = $topicName;
        $this->translator = $translator;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $topic = $this->session->createTopic($this->topicName);
        $newMessage = $this->translator->translate($message);

        $this->session->createProducer()->send($topic, $newMessage);
    }
}
