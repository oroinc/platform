<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\ORM\NonUniqueResultException;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateCompilationException;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateNotFoundException;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Psr\Log\LoggerInterface;
use Twig\Error\Error as TwigError;

/**
 * Provides compiled email template information ready to be sent via email.
 */
class EmailTemplateContentProvider
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var EmailRenderer
     */
    private $emailRenderer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EmailRenderer $emailRenderer
     * @param LoggerInterface $logger
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EmailRenderer $emailRenderer,
        LoggerInterface $logger
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->emailRenderer = $emailRenderer;
        $this->logger = $logger;
    }

    /**
     * @param EmailTemplateCriteria $criteria
     * @param string $language
     * @param array $params
     * @return EmailTemplateModel
     * @throws EmailTemplateNotFoundException
     * @throws EmailTemplateCompilationException
     */
    public function getTemplateContent(
        EmailTemplateCriteria $criteria,
        string $language,
        array $params
    ): EmailTemplateModel {
        $emailTemplate = $this->loadEmailTemplate($criteria, $language);

        try {
            list($subject, $content) = $this->emailRenderer->compileMessage($emailTemplate, $params);
        } catch (TwigError $exception) {
            $this->logger->error(
                sprintf(
                    'Rendering of email template "%s" failed. %s',
                    $emailTemplate->getName(),
                    $exception->getMessage()
                ),
                [
                    'locale' => $emailTemplate->getLocale(),
                    'entity_name' => $emailTemplate->getEntityName(),
                    'exception' => $exception,
                ]
            );

            throw new EmailTemplateCompilationException($criteria);
        }

        $emailTemplateModel = new EmailTemplateModel();
        $emailTemplateModel
            ->setSubject($subject)
            ->setContent($content)
            ->setType($this->getTemplateContentType($emailTemplate));

        return $emailTemplateModel;
    }

    /**
     * @param EmailTemplateInterface $emailTemplate
     * @return string
     */
    private function getTemplateContentType(EmailTemplateInterface $emailTemplate): string
    {
        return $emailTemplate->getType() === EmailTemplate::TYPE_HTML
            ? EmailTemplateModel::CONTENT_TYPE_HTML
            : EmailTemplateModel::CONTENT_TYPE_TEXT;
    }

    /**
     * @param EmailTemplateCriteria $criteria
     * @param string $language
     * @return EmailTemplate
     */
    private function loadEmailTemplate(EmailTemplateCriteria $criteria, string $language): EmailTemplate
    {
        /** @var EmailTemplateRepository $emailTemplateRepository */
        $emailTemplateRepository = $this->doctrineHelper->getEntityRepositoryForClass(EmailTemplate::class);

        try {
            $emailTemplate = $emailTemplateRepository->findOneLocalized($criteria, $language);
        } catch (NonUniqueResultException $exception) {
            $this->logger->error(
                'Could not find unique email template for the given criteria.',
                [
                    'name' => $criteria->getName(),
                    'entity_name' => $criteria->getEntityName(),
                    'language' => $language,
                    'exception' => $exception,
                ]
            );
            // If we have non unique result exception it can be treated similar to not found email template
            $emailTemplate = null;
        }

        if (!$emailTemplate) {
            throw new EmailTemplateNotFoundException($criteria);
        }

        return $emailTemplate;
    }
}
