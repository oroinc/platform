<?php

namespace Oro\Bundle\EmailBundle\Workflow\Action;

use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Tools\AggregatedEmailTemplatesSender;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Workflow action that schedules send emails based on passed templates
 */
class ScheduleSendEmailTemplate extends SendEmailTemplate
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var MessageProducerInterface */
    private $messageProducer;

    public function __construct(
        ContextAccessor $contextAccessor,
        Processor $emailProcessor,
        EmailAddressHelper $emailAddressHelper,
        EntityNameResolver $entityNameResolver,
        ValidatorInterface $validator,
        AggregatedEmailTemplatesSender $sender,
        DoctrineHelper $doctrineHelper,
        MessageProducerInterface $messageProducer
    ) {
        parent::__construct(
            $contextAccessor,
            $emailProcessor,
            $emailAddressHelper,
            $entityNameResolver,
            $validator,
            $sender
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
            Topics::SEND_EMAIL_TEMPLATE,
            [
                'from' => $this->getEmailAddress($context, $this->options['from']),
                'templateName' => $this->contextAccessor->getValue($context, $this->options['template']),
                'recipients' => array_map(
                    static function (EmailHolderInterface $holder) {
                        return $holder->getEmail();
                    },
                    $this->getRecipientsFromContext($context)
                ),
                'entity' => [
                    $this->doctrineHelper->getEntityClass($entity),
                    $this->doctrineHelper->getSingleEntityIdentifier($entity)
                ],
            ]
        );
    }
}
