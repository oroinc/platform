<?php

namespace Oro\Bundle\EmailBundle\Tools;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Provider\LocalizedTemplateProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Sends emails based on passed aggregated templates.
 */
class AggregatedEmailTemplatesSender implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var LocalizedTemplateProvider */
    private $localizedTemplateProvider;

    /** @var EmailOriginHelper */
    private $emailOriginHelper;

    /** @var Processor */
    private $emailProcessor;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        LocalizedTemplateProvider $localizedTemplateProvider,
        EmailOriginHelper $emailOriginHelper,
        Processor $emailProcessor
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->localizedTemplateProvider = $localizedTemplateProvider;
        $this->emailOriginHelper = $emailOriginHelper;
        $this->emailProcessor = $emailProcessor;
    }

    /**
     * @param object $entity
     * @param EmailHolderInterface[] $recipients
     * @param string $from
     * @param string $templateName
     * @return EmailUser[]
     *
     * @throws EntityNotFoundException if the specified email template cannot be found
     * @throws \Twig\Error\Error When an error occurred in Twig during email template loading, compilation or rendering
     */
    public function send(object $entity, array $recipients, string $from, string $templateName): array
    {
        $templateCollection = $this->localizedTemplateProvider->getAggregated(
            $recipients,
            new EmailTemplateCriteria($templateName, $this->doctrineHelper->getEntityClass($entity)),
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
     * @param object $entity
     * @param EmailHolderInterface[] $recipients
     * @param string $from
     * @param string $templateName
     * @param array $templateParams
     * @return EmailUser[]
     *
     * @throws EntityNotFoundException if the specified email template cannot be found
     * @throws \Twig\Error\Error When an error occurred in Twig during email template loading, compilation or rendering
     */
    public function sendWithParameters(
        object $entity,
        array $recipients,
        string $from,
        string $templateName,
        array $templateParams = []
    ): array {
        $templateCollection = $this->localizedTemplateProvider->getAggregated(
            $recipients,
            new EmailTemplateCriteria($templateName, $this->doctrineHelper->getEntityClass($entity)),
            array_merge(['entity' => $entity], $templateParams)
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
}
