<?php

namespace Oro\Bundle\MessageQueueBundle\Security;

use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Bundle\SecurityBundle\Exception\InvalidTokenSerializationException;
use Oro\Bundle\SecurityBundle\Exception\InvalidTokenUserOrganizationException;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The message consumption extension that replaces a current security token with the token
 * that is contained in a current message.
 * This provides an ability to process a message in the same security context
 * as a process that sent the message.
 * Also, the "security_agnostic_processors" option can be used to disable changing the security context
 * for some processors.
 * For details see {@link https://doc.oroinc.com/master/backend/mq/security-context/}.
 */
class SecurityAwareConsumptionExtension extends AbstractExtension
{
    /** @var array [processor name => TRUE, ...] */
    private array $securityAgnosticProcessors;
    private TokenStorageInterface $tokenStorage;
    private TokenSerializerInterface  $tokenSerializer;

    public function __construct(
        array $securityAgnosticProcessors,
        TokenStorageInterface $tokenStorage,
        TokenSerializerInterface $tokenSerializer
    ) {
        $this->securityAgnosticProcessors = array_fill_keys($securityAgnosticProcessors, true);
        $this->tokenStorage = $tokenStorage;
        $this->tokenSerializer = $tokenSerializer;
    }

    /**
     * {@inheritDoc}
     */
    public function onPreReceived(Context $context): void
    {
        if (isset($this->securityAgnosticProcessors[$context->getMessageProcessorName()])) {
            return;
        }

        // check whether a current message should be executed in own security context,
        // and if so, switch to the requested context
        $serializedToken = $context->getMessage()->getProperty(SecurityAwareDriver::PARAMETER_SECURITY_TOKEN);
        if ($serializedToken) {
            try {
                $token = $this->tokenSerializer->deserialize($serializedToken);
            } catch (InvalidTokenUserOrganizationException $exception) {
                // The data for the token is invalid, but this does not prevent the consumer from executing.
                // For example, a user was deleted before we started performing actions on them with the consumer, etc.
                $context->getLogger()->error($exception->getMessage());
                $context->setStatus(MessageProcessorInterface::REJECT);

                return;
            } catch (InvalidTokenSerializationException $exception) {
                // We expect that the token cannot be created and this error will prevent it from being used by
                // the consumer.
                $context->getLogger()->error($exception->getMessage());
                throw $exception;
            }

            $context->getLogger()->debug('Set security token');
            $this->tokenStorage->setToken($token);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function onPostReceived(Context $context): void
    {
        // reset the security context after processing of each message
        $this->tokenStorage->setToken(null);
    }
}
