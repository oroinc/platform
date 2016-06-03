<?php
namespace Oro\Component\MessageQueue\Consumption\Translator;

use Oro\Component\MessageQueue\Transport\MessageInterface;

interface MessageTranslatorInterface
{
    /**
     * @param MessageInterface $message
     *
     * @return MessageInterface
     */
    public function translate(MessageInterface $message);
}
