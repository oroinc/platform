<?php
namespace Oro\Component\MessageQueue\Consumption\Translator;

use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class DuplicateMessageTranslator implements MessageTranslatorInterface
{
    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * {@inheritdoc}
     */
    public function translate(MessageInterface $message)
    {
        return $this->session->createMessage($message->getBody(), $message->getProperties(), $message->getHeaders());
    }
}
