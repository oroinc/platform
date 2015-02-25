<?php

namespace Oro\Bundle\EmailBundle\Workflow\Action;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityNotFoundException;

use Oro\Bundle\EmailBundle\Validator\EmailAddressValidator;
use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Constraints\Email as EmailConstraints;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

/**
 * Class Processor
 *
 * @package Oro\Bundle\UserBundle\Mailer
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SendEmailTemplate extends AbstractSendEmail
{
    /** @var array */
    protected $options;

    /**  @var Processor */
    protected $emailProcessor;

    /** @var EmailRenderer */
    protected $renderer;

    /** @var ObjectManager */
    protected $objectManager;

    /** @var Validator */
    protected $validator;

    /**
     * @param ContextAccessor    $contextAccessor
     * @param EmailRenderer      $renderer
     * @param Processor          $emailProcessor
     * @param ObjectManager      $objectManager
     * @param EmailAddressHelper $emailAddressHelper
     * @param NameFormatter      $nameFormatter
     * @param Validator          $validator
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        EmailRenderer $renderer,
        Processor $emailProcessor,
        ObjectManager $objectManager,
        EmailAddressHelper $emailAddressHelper,
        NameFormatter $nameFormatter,
        Validator $validator
    ) {
        parent::__construct($contextAccessor, $emailAddressHelper, $nameFormatter);

        $this->renderer = $renderer;
        $this->emailProcessor = $emailProcessor;
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
        $emailModel = new Email();

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
            throw new EntityNotFoundException('Template not found');
        }
        $templateData = $this->renderer->compileMessage($emailTemplate, ['entity' => $entity]);

        $type = $emailTemplate->getType() == 'txt' ? 'text/plain' : 'text/html';
        list ($subjectRendered, $templateRendered) = $templateData;

        $emailModel->setSubject($subjectRendered);
        $emailModel->setBody($templateRendered);
        $emailModel->setType($type);

        $email = $this->emailProcessor->process($emailModel);

        if (array_key_exists('attribute', $this->options)) {
            $this->contextAccessor->setValue($context, $this->options['attribute'], $email);
        }
    }

    /**
     * @param string $email
     *
     * @return array
     */
    protected function validateAddress($email)
    {
        $emailConstraint = new EmailConstraints();
        $emailConstraint->message = 'Invalid email address';
        if ($email) {
            $errorList = $this->validator->validateValue(
                $email,
                $emailConstraint
            );
            if ($errorList && $errorList->count() > 0) {
                throw new ValidatorException($errorList->get(0)->getMessage());
            }
        }
    }
}
