<?php

namespace Oro\Bundle\EmailBundle\Workflow\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\DTO\EmailAddressDTO;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Provider\LocalizedTemplateProvider;
use Oro\Bundle\EmailBundle\Tools\AggregatedEmailTemplatesSender;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\Validator\Constraints\Email as EmailConstraints;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Workflow action that sends emails based on passed templates
 */
class SendEmailTemplate extends AbstractSendEmail
{
    /** @var array */
    protected $options;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var ValidatorInterface */
    private $validator;

    /** @var LocalizedTemplateProvider */
    private $localizedTemplateProvider;

    /** @var EmailOriginHelper */
    private $emailOriginHelper;

    /** @var EmailConstraints */
    private $emailConstraint;

    /** @var AggregatedEmailTemplatesSender|null */
    private $sender;

    /**
     * @param ContextAccessor $contextAccessor
     * @param Processor $emailProcessor
     * @param EmailAddressHelper $emailAddressHelper
     * @param EntityNameResolver $entityNameResolver
     * @param ManagerRegistry $registry
     * @param ValidatorInterface $validator
     * @param LocalizedTemplateProvider $localizedTemplateProvider
     * @param EmailOriginHelper $emailOriginHelper
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        Processor $emailProcessor,
        EmailAddressHelper $emailAddressHelper,
        EntityNameResolver $entityNameResolver,
        ManagerRegistry $registry,
        ValidatorInterface $validator,
        LocalizedTemplateProvider $localizedTemplateProvider,
        EmailOriginHelper $emailOriginHelper
    ) {
        parent::__construct($contextAccessor, $emailProcessor, $emailAddressHelper, $entityNameResolver);

        $this->registry = $registry;
        $this->validator = $validator;
        $this->localizedTemplateProvider = $localizedTemplateProvider;
        $this->emailOriginHelper = $emailOriginHelper;
    }

    /**
     * @param AggregatedEmailTemplatesSender|null $sender
     */
    public function setSender(?AggregatedEmailTemplatesSender $sender): void
    {
        $this->sender = $sender;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options): self
    {
        if (empty($options['from'])) {
            throw new InvalidParameterException('From parameter is required');
        }
        $this->assertEmailAddressOption($options['from']);

        if (empty($options['to']) && empty($options['recipients'])) {
            throw new InvalidParameterException('Need to specify "to" or "recipients" parameters');
        }

        $this->normalizeToOption($options);
        $this->normalizeRecipientsOption($options);

        if (empty($options['template'])) {
            throw new InvalidParameterException('Template parameter is required');
        }

        if (empty($options['entity'])) {
            throw new InvalidParameterException('Entity parameter is required');
        }

        $this->options = $options;
        $this->emailConstraint = new EmailConstraints(['message' => 'Invalid email address']);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws EntityNotFoundException if the specified email template cannot be found
     * @throws \Twig\Error\Error When an error occurred in Twig during email template loading, compilation or rendering
     */
    protected function executeAction($context): void
    {
        $from = $this->getEmailAddress($context, $this->options['from']);
        $this->validateEmailAddress($from, '"From" email');

        $templateName = $this->contextAccessor->getValue($context, $this->options['template']);
        $entity = $this->contextAccessor->getValue($context, $this->options['entity']);
        $recipients = $this->getRecipientsFromContext($context);

        if ($this->sender) {
            $emailUsers = $this->sender->send($entity, $recipients, $from, $templateName);
        } else {
            $emailUsers = $this->send($entity, $recipients, $from, $templateName);
        }

        $emailUser = reset($emailUsers);
        if (array_key_exists('attribute', $this->options) && $emailUser instanceof EmailUser) {
            $this->contextAccessor->setValue($context, $this->options['attribute'], $emailUser->getEmail());
        }
    }

    /**
     * @param $entity
     * @param array $recipients
     * @param string $from
     * @param string $templateName
     *
     * @return array
     */
    private function send($entity, array $recipients, string $from, string $templateName): array
    {
        $entityClassName = $this->registry->getManagerForClass(\get_class($entity))
            ->getClassMetadata(\get_class($entity))
            ->getName();

        $templateCollection = $this->localizedTemplateProvider->getAggregated(
            $recipients,
            new EmailTemplateCriteria($templateName, $entityClassName),
            ['entity' => $entity]
        );

        $emailUsers = [];
        foreach ($templateCollection as $localizedTemplateDTO) {
            $emailTemplate = $localizedTemplateDTO->getEmailTemplate();

            $emailModel = new Email();
            $emailModel->setFrom($from);
            $emailModel->setTo($localizedTemplateDTO->getEmails());
            $emailModel->setSubject($emailTemplate->getSubject());
            $emailModel->setBody($emailTemplate->getContent());
            $emailModel->setType($emailTemplate->getType() === EmailTemplate::CONTENT_TYPE_HTML ? 'html' : 'text');

            try {
                $emailOrigin = $this->emailOriginHelper->getEmailOrigin(
                    $emailModel->getFrom(),
                    $emailModel->getOrganization()
                );

                $emailUsers[] = $this->emailProcessor->process($emailModel, $emailOrigin);
            } catch (\Swift_SwiftException $exception) {
                $this->logger->error('Workflow send email template action.', ['exception' => $exception]);
            }
        }

        return $emailUsers;
    }

    /**
     * @param mixed $context
     * @return array
     */
    protected function getRecipientsFromContext($context): array
    {
        $recipients = [];
        foreach ($this->options['to'] as $email) {
            if ($email) {
                $address = $this->getEmailAddress($context, $email);
                if ($address) {
                    $this->validateEmailAddress($address, 'Recipient email');
                    $recipients[] = new EmailAddressDTO($address);
                }
            }
        }

        foreach ($this->options['recipients'] as $recipient) {
            if ($recipient) {
                $recipients[] = $this->contextAccessor->getValue($context, $recipient);
            }
        }

        return $recipients;
    }

    /**
     * @deprecated Use validateEmailAddress() instead
     *
     * @param string $email
     * @throws ValidatorException if email address is not valid
     */
    protected function validateAddress($email)
    {
        return $this->validateEmailAddress($email);
    }

    /**
     * @param string $email
     * @param string $context optional description of what kind of address is being validated
     * @throws ValidatorException if email address is not valid
     */
    protected function validateEmailAddress($email, string $context = '')
    {
        $errorList = $this->validator->validate($email, $this->emailConstraint);

        if ($errorList && $errorList->count() > 0) {
            $errorString = $errorList->get(0)->getMessage();
            throw new ValidatorException(\sprintf("Validating %s (%s):\n%s", $context, $email, $errorString));
        }
    }

    /**
     * @param array $options
     */
    private function normalizeToOption(array &$options): void
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

    /**
     * @param array $options
     * @throws InvalidParameterException
     */
    private function normalizeRecipientsOption(array &$options): void
    {
        if (empty($options['recipients'])) {
            $options['recipients'] = [];
        }

        if (!is_array($options['recipients'])) {
            throw new InvalidParameterException(
                \sprintf('Recipients parameter must be an array, %s given', \gettype($options['recipients']))
            );
        }
    }
}
