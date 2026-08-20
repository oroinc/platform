<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Form\Handler;

use Oro\Bundle\UserBundle\Async\Topic\AbstractPasswordResetRequestTopic;
use Oro\Bundle\UserBundle\Provider\UserLoggingInfoProviderInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base handler for the forgot password forms.
 */
abstract class AbstractPasswordResetRequestHandler
{
    public function __construct(
        protected readonly MessageProducerInterface $messageProducer,
        protected readonly UserLoggingInfoProviderInterface $userLoggingInfoProvider,
        protected readonly LoggerInterface $logger
    ) {
    }

    public function process(FormInterface $form, Request $request): ?string
    {
        if (!$request->isMethod(Request::METHOD_POST)) {
            return null;
        }

        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return null;
        }

        $userIdentifier = (string)$form->get($this->getFieldName())->getData();

        $this->messageProducer->send($this->getTopicName(), $this->createMessageBody($userIdentifier));

        $this->logger->notice(
            'Reset password email has been requested.',
            $this->userLoggingInfoProvider->getUserLoggingInfo($userIdentifier)
        );

        return $userIdentifier;
    }

    protected function createMessageBody(string $userIdentifier): array
    {
        return [AbstractPasswordResetRequestTopic::USER_IDENTIFIER => $userIdentifier];
    }

    abstract protected function getFieldName(): string;

    abstract protected function getTopicName(): string;
}
