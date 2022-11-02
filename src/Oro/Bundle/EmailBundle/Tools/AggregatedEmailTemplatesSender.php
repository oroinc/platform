<?php

namespace Oro\Bundle\EmailBundle\Tools;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Provider\LocalizedTemplateProvider;
use Oro\Bundle\EmailBundle\Sender\EmailModelSender;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Sends localized emails to specified recipients using specified email template.
 * Creates {@see EmailUser} entities.
 */
class AggregatedEmailTemplatesSender implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private DoctrineHelper $doctrineHelper;

    private LocalizedTemplateProvider $localizedTemplateProvider;

    private EmailOriginHelper $emailOriginHelper;

    private EmailModelSender $emailModelSender;

    private EntityOwnerAccessor $entityOwnerAccessor;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        LocalizedTemplateProvider $localizedTemplateProvider,
        EmailOriginHelper $emailOriginHelper,
        EmailModelSender $emailModelSender,
        EntityOwnerAccessor $entityOwnerAccessor
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->localizedTemplateProvider = $localizedTemplateProvider;
        $this->emailOriginHelper = $emailOriginHelper;
        $this->emailModelSender = $emailModelSender;
        $this->entityOwnerAccessor = $entityOwnerAccessor;
    }

    /**
     * @param object $entity
     * @param EmailHolderInterface[] $recipients
     * @param From $from
     * @param string $templateName
     * @param array $templateParams
     * @return EmailUser[]
     *
     * @throws \Doctrine\ORM\EntityNotFoundException If the specified email template cannot be found
     * @throws \Twig\Error\Error When an error occurred in Twig during email template loading, compilation or rendering
     */
    public function send(
        object $entity,
        array $recipients,
        From $from,
        string $templateName,
        array $templateParams = []
    ): array {
        $templateCollection = $this->localizedTemplateProvider->getAggregated(
            $recipients,
            new EmailTemplateCriteria($templateName, $this->doctrineHelper->getEntityClass($entity)),
            array_merge(['entity' => $entity], $templateParams)
        );

        $entityOrganization = $this->entityOwnerAccessor->getOrganization($entity);
        $emailUsers = [];
        foreach ($templateCollection as $localizedTemplateDTO) {
            $emailTemplate = $localizedTemplateDTO->getEmailTemplate();

            $emailModel = new Email();
            $emailModel->setFrom($from->toString());
            $emailModel->setTo($localizedTemplateDTO->getEmails());
            $emailModel->setSubject($emailTemplate->getSubject());
            $emailModel->setBody($emailTemplate->getContent());
            $emailModel->setType($emailTemplate->getType() === EmailTemplate::CONTENT_TYPE_HTML ? 'html' : 'text');
            if ($entityOrganization) {
                $emailModel->setOrganization($entityOrganization);
            }

            try {
                $emailOrigin = $this->emailOriginHelper->getEmailOrigin(
                    $emailModel->getFrom(),
                    $emailModel->getOrganization()
                );

                $emailUsers[] = $this->emailModelSender->send($emailModel, $emailOrigin);
            } catch (\RuntimeException $exception) {
                $this->logger->error(
                    sprintf(
                        'Failed to send an email to %s using "%s" email template for "%s" entity: %s',
                        implode(', ', (array)$emailModel->getTo()),
                        $templateName,
                        get_debug_type($entity),
                        $exception->getMessage()
                    ),
                    ['exception' => $exception]
                );
            }
        }

        return $emailUsers;
    }
}
