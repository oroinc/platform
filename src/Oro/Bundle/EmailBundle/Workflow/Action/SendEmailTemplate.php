<?php

namespace Oro\Bundle\EmailBundle\Workflow\Action;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityNotFoundException;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Constraints\Email as EmailConstraints;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options['from'])) {
            throw new InvalidParameterException('From parameter is required');
        }
        $this->assertEmailAddressOption($options['from']);

        if (empty($options['to'])) {
            throw new InvalidParameterException('To parameter is required');
        }
        if (!is_array($options['to'])
            || array_key_exists('name', $options['to'])
            || array_key_exists('email', $options['to'])
        ) {
            $options['to'] = array($options['to']);
        }
        foreach ($options['to'] as $to) {
            $this->assertEmailAddressOption($to);
        }

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
     */
    protected function executeAction($context)
    {
        $emailModel = $this->getEmailModel();

        $from = $this->getEmailAddress($context, $this->options['from']);
        $this->validateAddress($from);
        $emailModel->setFrom($from);
        $to = [];
        foreach ($this->options['to'] as $email) {
            if ($email) {
                $address = $this->getEmailAddress($context, $email);
                $this->validateAddress($address);
                $to[] = $this->getEmailAddress($context, $address);
            }
        }
        $emailModel->setTo($to);
        $entity = $this->contextAccessor->getValue($context, $this->options['entity']);
        $template = $this->contextAccessor->getValue($context, $this->options['template']);

        $emailTemplate = $this->objectManager->getRepository('OroEmailBundle:EmailTemplate')
            ->findByName($template);
        if (!$emailTemplate) {
            $errorMessage = sprintf('Template "%s" not found.', $template);
            $this->logger->error('Workflow send email action.' . $errorMessage);
            throw new EntityNotFoundException($errorMessage);
        }
        $templateData = $this->renderer->compileMessage($emailTemplate, ['entity' => $entity]);

        list ($subjectRendered, $templateRendered) = $templateData;

        $emailModel->setSubject($subjectRendered);
        $emailModel->setBody($templateRendered);
        $emailModel->setType($emailTemplate->getType());

        $emailUser = $this->emailProcessor->process(
            $emailModel,
            $this->emailProcessor->getEmailOrigin($emailModel->getFrom(), $emailModel->getOrganization())
        );

        if (array_key_exists('attribute', $this->options)) {
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
     * @return Email
     */
    protected function getEmailModel()
    {
        return new Email();
    }
}
