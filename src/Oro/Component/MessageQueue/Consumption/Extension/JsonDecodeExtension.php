<?php
namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension;
use Oro\Component\MessageQueue\Consumption\ExtensionTrait;
use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;

class JsonDecodeExtension implements Extension
{
    use ExtensionTrait;

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        $message = $context->getMessage();
        if ('application/json' == $message->getHeader('content_type')) {
            $context->getLogger()->debug('[JsonDecodeExtension] Messages content is json. Try to decode it');

            $body = json_decode($message->getBody(), true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new InvalidMessageException(sprintf(
                    'The message content type is a json but the body is not valid json. Code: %s Error: %s',
                    json_last_error(),
                    json_last_error_msg()
                ));
            }

            $localProperties = $message->getLocalProperties();
            $localProperties['json_body'] = $body;
            $message->setLocalProperties($localProperties);
            
            $context->getLogger()->debug('[JsonDecodeExtension] Set json_body local property');
        }
    }
}
