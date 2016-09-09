<?php

namespace Oro\Bundle\EmailBundle\Workflow\Action;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;

class SendEmail extends AbstractSendEmail
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @param ContextAccessor    $contextAccessor
     * @param Processor          $emailProcessor
     * @param EmailAddressHelper $emailAddressHelper
     * @param EntityNameResolver $entityNameResolver
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        Processor $emailProcessor,
        EmailAddressHelper $emailAddressHelper,
        EntityNameResolver $entityNameResolver
    ) {
        parent::__construct($contextAccessor, $emailProcessor, $emailAddressHelper, $entityNameResolver);
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
    protected function executeAction($context)
    {
        $type = 'txt';
        $emailModel = new Email();
        $emailModel->setFrom($this->getEmailAddress($context, $this->options['from']));
        $to = [];
        foreach ($this->options['to'] as $email) {
            if ($email) {
                $to[] = $this->getEmailAddress($context, $email);
            }
        }
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
        $emailModel->setType($type);

        $emailUser = $this->emailProcessor->process(
            $emailModel,
            $this->emailProcessor->getEmailOrigin($emailModel->getFrom(), $emailModel->getOrganization())
        );

        if (array_key_exists('attribute', $this->options)) {
            $this->contextAccessor->setValue($context, $this->options['attribute'], $emailUser->getEmail());
        }
    }
}
