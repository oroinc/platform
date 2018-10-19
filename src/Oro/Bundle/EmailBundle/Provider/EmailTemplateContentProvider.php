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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Provides compiled email template information ready to be sent via email.
 */
class EmailTemplateContentProvider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var EmailRenderer
     */
    private $emailRenderer;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EmailRenderer $emailRenderer
     */
    public function __construct(DoctrineHelper $doctrineHelper, EmailRenderer $emailRenderer)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->emailRenderer = $emailRenderer;
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
            [$subject, $content] = $this->emailRenderer->compileMessage($emailTemplate, $params);
        } catch (\Twig_Error $exception) {
            $this->logError(
                sprintf(
                    'Rendering of email template "%s" failed. %s',
                    $emailTemplate->getSubject(),
                    $exception->getMessage()
                ),
                ['exception' => $exception]
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
     * @param string $message
     * @param array $params
     */
    private function logError(string $message, array $params = []): void
    {
        if (!$this->logger) {
            return;
        }

        $this->logger->error($message, $params);
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
            $this->logError(
                'Could not find unique email template for the given criteria',
                ['exception' => $exception, 'criteria' => $criteria]
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
