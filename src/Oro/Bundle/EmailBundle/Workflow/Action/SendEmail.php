<?php

namespace Oro\Bundle\EmailBundle\Workflow\Action;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Sender\EmailModelSender;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Workflow action that sends email
 */
class SendEmail extends AbstractSendEmail
{
    private array $options;

    private EmailModelSender $emailModelSender;

    private EmailOriginHelper $emailOriginHelper;

    public function __construct(
        ContextAccessor $contextAccessor,
        ValidatorInterface $validator,
        EmailAddressHelper $emailAddressHelper,
        EntityNameResolver $entityNameResolver,
        EmailModelSender $emailModelSender,
        EmailOriginHelper $emailOriginHelper
    ) {
        parent::__construct($contextAccessor, $validator, $emailAddressHelper, $entityNameResolver);

        $this->emailModelSender = $emailModelSender;
        $this->emailOriginHelper = $emailOriginHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options): self
    {
        $this->assertFrom($options);

        if (empty($options['to'])) {
            throw new InvalidParameterException('To parameter is required');
        }

        $this->normalizeToOption($options);

        if (empty($options['subject'])) {
            throw new InvalidParameterException('Subject parameter is required');
        }
        if (empty($options['body'])) {
            throw new InvalidParameterException('Body parameter is required');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context): void
    {
        $from = $this->getEmailAddress($context, $this->options['from']);
        $this->validateEmailAddress($from, '"From" email');

        $emailModel = new EmailModel();
        $emailModel->setFrom($from);
        $to = $this->getRecipients($context, $this->options);

        $emailModel->setTo($to);
        $emailModel->setSubject(
            $this->contextAccessor->getValue($context, $this->options['subject'])
        );
        $emailModel->setBody(
            $this->contextAccessor->getValue($context, $this->options['body'])
        );
        if (array_key_exists('type', $this->options) && in_array($this->options['type'], ['txt', 'html'], true)) {
            $type = $this->options['type'];
        }
        $emailModel->setType($type ?? 'txt');

        $emailUser = null;
        try {
            $emailOrigin = $this->emailOriginHelper
                ->getEmailOrigin($emailModel->getFrom(), $emailModel->getOrganization());

            $emailUser = $this->emailModelSender->send($emailModel, $emailOrigin);
        } catch (\RuntimeException $exception) {
            $this->logger->error(
                sprintf('Failed to send an email to %s: %s', implode(',', $to), $exception->getMessage()),
                ['exception' => $exception, 'emailModel' => $emailModel]
            );
        }

        if (array_key_exists('attribute', $this->options) && $emailUser instanceof EmailUser) {
            $this->contextAccessor->setValue($context, $this->options['attribute'], $emailUser->getEmail());
        }
    }
}
