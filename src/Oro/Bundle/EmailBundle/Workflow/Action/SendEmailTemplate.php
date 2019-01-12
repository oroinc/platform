<?php

namespace Oro\Bundle\EmailBundle\Workflow\Action;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Provider\PreferredLanguageProviderInterface;
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

    /** @var EmailRenderer */
    protected $renderer;

    /** @var ObjectManager */
    protected $objectManager;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var PreferredLanguageProviderInterface */
    private $languageProvider;

    /**
     * @param ContextAccessor    $contextAccessor
     * @param Processor          $emailProcessor
     * @param EmailAddressHelper $emailAddressHelper
     * @param EntityNameResolver $entityNameResolver
     * @param EmailRenderer      $renderer
     * @param ObjectManager      $objectManager
     * @param ValidatorInterface $validator
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        Processor $emailProcessor,
        EmailAddressHelper $emailAddressHelper,
        EntityNameResolver $entityNameResolver,
        EmailRenderer $renderer,
        ObjectManager $objectManager,
        ValidatorInterface $validator
    ) {
        parent::__construct($contextAccessor, $emailProcessor, $emailAddressHelper, $entityNameResolver);

        $this->renderer = $renderer;
        $this->objectManager = $objectManager;
        $this->validator = $validator;
    }

    /**
     * @param PreferredLanguageProviderInterface $languageProvider
     */
    public function setPreferredLanguageProvider(PreferredLanguageProviderInterface $languageProvider)
    {
        $this->languageProvider = $languageProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
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

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws EntityNotFoundException if the specified email template cannot be found
     * @throws \Twig_Error When an error occurred in Twig during email template loading, compilation or rendering
     */
    protected function executeAction($context)
    {
        $emailUsers = [];
        $entity = $this->contextAccessor->getValue($context, $this->options['entity']);
        $template = $this->contextAccessor->getValue($context, $this->options['template']);
        $from = $this->getEmailAddress($context, $this->options['from']);
        $this->validateAddress($from);

        $recipientsByGroups = $this->getRecipientsByLanguage($context);
        $entityClass = $this->objectManager->getClassMetadata(get_class($entity))->getName();
        foreach ($recipientsByGroups as $language => $recipients) {
            $criteria = new EmailTemplateCriteria($template, $entityClass);
            $emailTemplate = $this->getEmailTemplate($criteria, $language);
            list($subject, $body) = $this->renderer->compileMessage(
                $emailTemplate,
                ['entity' => $entity]
            );
            $emails = array_map(function ($recipient) {
                return $recipient instanceof EmailHolderInterface ? $recipient->getEmail() : $recipient;
            }, $recipients);

            $emailModel = $this->getEmailModel();
            $emailModel->setFrom($from);
            $emailModel->setTo($emails);
            $emailModel->setSubject($subject);
            $emailModel->setBody($body);
            $emailModel->setType($emailTemplate->getType());

            try {
                $emailUsers[] = $this->emailProcessor->process(
                    $emailModel,
                    $this->emailProcessor->getEmailOrigin($emailModel->getFrom(), $emailModel->getOrganization())
                );
            } catch (\Swift_SwiftException $exception) {
                $this->logger->error('Workflow send email template action.', ['exception' => $exception]);
            }
        }

        $emailUser = reset($emailUsers);
        if (array_key_exists('attribute', $this->options) && $emailUser instanceof EmailUser) {
            $this->contextAccessor->setValue($context, $this->options['attribute'], $emailUser->getEmail());
        }
    }

    /**
     * @param string $email
     *
     * @throws \Symfony\Component\Validator\Exception\ValidatorException
     */
    protected function validateAddress($email)
    {
        $emailConstraint = new EmailConstraints();
        $emailConstraint->message = 'Invalid email address';
        if ($email) {
            $errorList = $this->validator->validate(
                $email,
                $emailConstraint
            );
            if ($errorList && $errorList->count() > 0) {
                throw new ValidatorException($errorList->get(0)->getMessage());
            }
        }
    }

    /**
     * @param mixed $context
     * @return array
     */
    private function getRecipientsByLanguage($context): array
    {
        $recipients = [];
        foreach ($this->options['to'] as $email) {
            if ($email) {
                $address = $this->getEmailAddress($context, $email);
                if ($address) {
                    $this->validateAddress($address);
                    $recipients[] = $this->getEmailAddress($context, $address);
                }
            }
        }
        foreach ($this->options['recipients'] as $recipient) {
            if ($recipient) {
                $recipient = $this->contextAccessor->getValue($context, $recipient);
                $this->validateRecipient($recipient);
                $recipients[] = $recipient;
            }
        }

        $groupedRecipients = [];
        foreach ($recipients as $recipient) {
            $groupedRecipients[$this->languageProvider->getPreferredLanguage($recipient)][] = $recipient;
        }

        // Move default language on first place in array
        if (isset($groupedRecipients[Configuration::DEFAULT_LANGUAGE])) {
            $defaultLangRecipients = $groupedRecipients[Configuration::DEFAULT_LANGUAGE];
            unset($groupedRecipients[Configuration::DEFAULT_LANGUAGE]);
            $groupedRecipients = [Configuration::DEFAULT_LANGUAGE => $defaultLangRecipients] + $groupedRecipients;
        }

        return $groupedRecipients;
    }

    /**
     * @param EmailTemplateCriteria $criteria
     * @param string $language
     * @throws EntityNotFoundException
     * @return EmailTemplate
     */
    private function getEmailTemplate(EmailTemplateCriteria $criteria, string $language): EmailTemplate
    {
        $emailTemplate = $this->objectManager
            ->getRepository(EmailTemplate::class)
            ->findOneLocalized($criteria, $language);

        if (!$emailTemplate) {
            $errorMessage = sprintf(
                'Workflow @send_email_template action error: '
                . 'template "%s" for entity "%s" and language "%s" not found.',
                $criteria->getName(),
                $criteria->getEntityName(),
                $language
            );
            $this->logger->error($errorMessage);
            throw new EntityNotFoundException($errorMessage);
        }

        return $emailTemplate;
    }

    /**
     * @param EmailHolderInterface $recipient
     * @throws ValidatorException
     */
    private function validateRecipient(EmailHolderInterface $recipient): void
    {
        if (!$recipient instanceof EmailHolderInterface) {
            throw new ValidatorException(sprintf(
                'Recipient should implements %s, but %s was given',
                EmailHolderInterface::class,
                is_object($recipient) ? get_class($recipient) : gettype($recipient)
            ));
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
            throw new InvalidParameterException('Recipients parameter must be an array');
        }
    }

    /**
     * @return Email
     */
    protected function getEmailModel()
    {
        return new Email();
    }
}
