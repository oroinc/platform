<?php

namespace Oro\Bundle\EmailBundle\Workflow\Action;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Tools\AggregatedEmailTemplatesSender;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Workflow action that sends emails based on passed templates
 */
class SendEmailTemplate extends AbstractSendEmailTemplate
{
    private AggregatedEmailTemplatesSender $aggregatedEmailTemplatesSender;

    public function __construct(
        ContextAccessor $contextAccessor,
        ValidatorInterface $validator,
        EmailAddressHelper $emailAddressHelper,
        EntityNameResolver $entityNameResolver,
        AggregatedEmailTemplatesSender $aggregatedEmailTemplatesSender
    ) {
        parent::__construct($contextAccessor, $validator, $emailAddressHelper, $entityNameResolver);

        $this->aggregatedEmailTemplatesSender = $aggregatedEmailTemplatesSender;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Doctrine\ORM\EntityNotFoundException if the specified email template cannot be found
     * @throws \Twig\Error\Error When an error occurred in Twig during email template loading, compilation or rendering
     */
    protected function executeAction($context): void
    {
        $from = $this->getEmailAddress($context, $this->options['from']);
        $this->validateEmailAddress($from, '"From" email');

        $templateName = $this->contextAccessor->getValue($context, $this->options['template']);

        $entity = $this->contextAccessor->getValue($context, $this->options['entity']);

        $emailUsers = $this->aggregatedEmailTemplatesSender->send(
            $entity,
            $this->getRecipients($context, $this->options),
            From::emailAddress($from),
            $templateName
        );

        $emailUser = reset($emailUsers);
        if (array_key_exists('attribute', $this->options) && $emailUser instanceof EmailUser) {
            $this->contextAccessor->setValue($context, $this->options['attribute'], $emailUser->getEmail());
        }
    }
}
