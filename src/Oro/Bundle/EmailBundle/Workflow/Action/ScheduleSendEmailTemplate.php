<?php

namespace Oro\Bundle\EmailBundle\Workflow\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Provider\LocalizedTemplateProvider;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Workflow action that schedules send emails based on passed templates
 */
class ScheduleSendEmailTemplate extends SendEmailTemplate
{
    /** @var MessageProducerInterface */
    private $messageProducer;

    /**
     * @param ContextAccessor $contextAccessor
     * @param Processor $emailProcessor
     * @param EmailAddressHelper $emailAddressHelper
     * @param EntityNameResolver $entityNameResolver
     * @param ManagerRegistry $registry
     * @param ValidatorInterface $validator
     * @param LocalizedTemplateProvider $localizedTemplateProvider
     * @param EmailOriginHelper $emailOriginHelper
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        Processor $emailProcessor,
        EmailAddressHelper $emailAddressHelper,
        EntityNameResolver $entityNameResolver,
        ManagerRegistry $registry,
        ValidatorInterface $validator,
        LocalizedTemplateProvider $localizedTemplateProvider,
        EmailOriginHelper $emailOriginHelper,
        MessageProducerInterface $messageProducer
    ) {
        parent::__construct(
            $contextAccessor,
            $emailProcessor,
            $emailAddressHelper,
            $entityNameResolver,
            $registry,
            $validator,
            $localizedTemplateProvider,
            $emailOriginHelper
        );

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
                'from' => $from,
                'templateName' => $this->contextAccessor->getValue($context, $this->options['template']),
                'recipients' => array_map(
                    static function (EmailHolderInterface $holder) {
                        return $holder->getEmail();
                    },
                    $this->getRecipientsFromContext($context)
                ),
                'entity' => [ClassUtils::getRealClass($entity), $this->getEntityIdentifier($entity)],
            ]
        );
    }

    /**
     * @param $entity
     *
     * @return mixed|null
     */
    private function getEntityIdentifier($entity)
    {
        if (method_exists($entity, 'getId')) {
            return $entity->getId();
        }

        $className = \get_class($entity);
        $classMetadata = $this->registry->getManagerForClass($className)->getClassMetadata($className);
        $identifier = $classMetadata->getIdentifierValues($entity);

        return $identifier ? reset($identifier) : null;
    }
}
