<?php

namespace Oro\Bundle\EmailBundle\Workflow\Action;

use Oro\Bundle\EmailBundle\Async\Topic\SendEmailTemplateTopic;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Workflow action that schedules send emails based on passed templates
 */
class ScheduleSendEmailTemplate extends AbstractSendEmailTemplate
{
    private DoctrineHelper $doctrineHelper;

    private MessageProducerInterface $messageProducer;

    public function __construct(
        ContextAccessor $contextAccessor,
        ValidatorInterface $validator,
        EmailAddressHelper $emailAddressHelper,
        EntityNameResolver $entityNameResolver,
        DoctrineHelper $doctrineHelper,
        MessageProducerInterface $messageProducer
    ) {
        parent::__construct(
            $contextAccessor,
            $validator,
            $emailAddressHelper,
            $entityNameResolver
        );

        $this->doctrineHelper = $doctrineHelper;
        $this->messageProducer = $messageProducer;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context): void
    {
        $from = $this->getEmailAddress($context, $this->options['from']);
        $this->validateEmailAddress($from, '"From" email');
        $entity = $this->contextAccessor->getValue($context, $this->options['entity']);

        $this->messageProducer->send(
            SendEmailTemplateTopic::getName(),
            [
                'from' => $this->getEmailAddress($context, $this->options['from']),
                'templateName' => $this->contextAccessor->getValue($context, $this->options['template']),
                'recipients' => array_map(
                    static function (EmailHolderInterface $holder) {
                        return $holder->getEmail();
                    },
                    $this->getRecipients($context, $this->options),
                ),
                'entity' => [
                    $this->doctrineHelper->getEntityClass($entity),
                    $this->doctrineHelper->getSingleEntityIdentifier($entity),
                ],
            ]
        );
    }
}
