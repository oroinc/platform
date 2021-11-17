<?php

namespace Oro\Bundle\EmailBundle\Workflow\Action;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Base class for email sending actions.
 */
abstract class AbstractSendEmail extends AbstractAction implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected ValidatorInterface $validator;

    protected EmailAddressHelper $emailAddressHelper;

    protected EntityNameResolver $entityNameResolver;

    public function __construct(
        ContextAccessor $contextAccessor,
        ValidatorInterface $validator,
        EmailAddressHelper $emailAddressHelper,
        EntityNameResolver $entityNameResolver
    ) {
        parent::__construct($contextAccessor);

        $this->validator = $validator;
        $this->emailAddressHelper = $emailAddressHelper;
        $this->entityNameResolver = $entityNameResolver;
        $this->logger = new NullLogger();
    }

    protected function assertFrom(array $options): void
    {
        if (empty($options['from'])) {
            throw new InvalidParameterException('From parameter is required');
        }

        $this->assertEmailAddressOption($options['from']);
    }

    /**
     * @throws InvalidParameterException
     */
    protected function assertEmailAddressOption(array|string $option): void
    {
        if (is_array($option) && array_key_exists('name', $option) && !array_key_exists('email', $option)) {
            throw new InvalidParameterException('Email parameter is required');
        }
    }

    /**
     * @param string $email
     * @param string $context Optional description of what kind of address is being validated
     * @throws ValidatorException If email address is not valid
     */
    protected function validateEmailAddress(string $email, string $context = ''): void
    {
        $errorList = $this->validator->validate($email, new EmailConstraint());

        if ($errorList && $errorList->count() > 0) {
            $errorString = $errorList->get(0)->getMessage();
            throw new ValidatorException(\sprintf("Validating %s (%s):\n%s", $context, $email, $errorString));
        }
    }

    /**
     * Get email address prepared for sending.
     */
    protected function getEmailAddress(object|array $context, PropertyPathInterface|array|string $data): string
    {
        $name = null;
        $emailAddress = $this->contextAccessor->getValue($context, $data);

        if (is_array($data)) {
            $emailAddress = $this->contextAccessor->getValue($context, $data['email']);

            if (array_key_exists('name', $data)) {
                $data['name'] = $this->contextAccessor->getValue($context, $data['name']);

                if (is_object($data['name'])) {
                    $name = $this->entityNameResolver->getName($data['name']);
                } else {
                    $name = $data['name'];
                }
            }
        }

        return $this->emailAddressHelper->buildFullEmailAddress($emailAddress, $name);
    }

    protected function normalizeToOption(array &$options): void
    {
        if (empty($options['to'])) {
            $options['to'] = [];
        }

        if (!is_array($options['to'])
            || array_key_exists('name', $options['to'])
            || array_key_exists('email', $options['to'])
        ) {
            $options['to'] = [$options['to']];
        }

        foreach ($options['to'] as $to) {
            $this->assertEmailAddressOption($to);
        }
    }

    protected function getRecipients(object|array $context, array $options): array
    {
        $recipients = [];
        foreach ($options['to'] as $email) {
            if ($email) {
                $address = $this->getEmailAddress($context, $email);
                if ($address) {
                    $this->validateEmailAddress($address, 'Recipient email');
                    $recipients[] = $address;
                }
            }
        }

        return $recipients;
    }
}
