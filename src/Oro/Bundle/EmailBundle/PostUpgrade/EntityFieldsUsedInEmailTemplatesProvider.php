<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\PostUpgrade;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Event\EmailTemplateSecurityPolicyCheckBefore;
use Oro\Bundle\EntityBundle\Twig\Analyzer\TemplateAccessAnalyzer;
use Oro\Bundle\EntityBundle\Twig\Analyzer\TemplateAccessEntry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Error\SyntaxError;

/**
 * Iterates all email templates stored in the database, statically analyzes their Twig content,
 * and returns a deduplicated list of entity field accesses found across all templates.
 */
class EntityFieldsUsedInEmailTemplatesProvider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly TemplateAccessAnalyzer $templateAccessAnalyzer,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * Returns a deduplicated list of fields accessed in all email templates.
     *
     * @return list<array{entity: string, field: string}>
     */
    public function getEntityFieldsUsedInEmailTemplates(): array
    {
        $emailTemplateRepository = $this->doctrine->getRepository(EmailTemplate::class);

        $entityFields = [];

        /**
         * @var EmailTemplate $emailTemplate
         */
        foreach ($emailTemplateRepository->getBatchIterator() as $emailTemplate) {
            $entityClass = $emailTemplate->getEntityName();
            $variableTypes = $entityClass !== null ? ['entity' => $entityClass] : [];
            $variableTypes = $this->dispatchBeforeEvent($emailTemplate, $variableTypes);

            $entityFields += $this->analyzeSources(
                $this->collectSources($emailTemplate),
                $variableTypes,
                $emailTemplate->getName()
            );
        }

        return array_values($entityFields);
    }

    /**
     * Collects all Twig source strings to analyze for a given template:
     * the base content and subject (which serve as the fallback for all localizations),
     * plus the content and subject of any translation that overrides the base (fallback = false).
     *
     * @return list<?string>
     */
    private function collectSources(EmailTemplate $emailTemplate): array
    {
        $templateSources = [$emailTemplate->getContent(), $emailTemplate->getSubject()];

        foreach ($emailTemplate->getTranslations() as $translation) {
            if (!$translation->isContentFallback()) {
                $templateSources[] = $translation->getContent();
            }
            if (!$translation->isSubjectFallback()) {
                $templateSources[] = $translation->getSubject();
            }
        }

        return $templateSources;
    }

    /**
     * Analyzes a list of Twig source strings and returns all resolved property accesses.
     *
     * @param list<?string> $templateSources
     * @param array<string, string> $variableTypes
     *
     * @return array<string, array{entity: string, field: string}>
     */
    private function analyzeSources(array $templateSources, array $variableTypes, string $templateName): array
    {
        $entityFields = [];

        foreach ($templateSources as $templateSource) {
            if (!\is_string($templateSource) || $templateSource === '') {
                continue;
            }

            try {
                $accessEntries = $this->templateAccessAnalyzer->analyzeTemplate($templateSource, $variableTypes);
            } catch (SyntaxError $e) {
                $this->logger->warning(
                    'Found syntax error in email template "{template}": {message}',
                    ['template' => $templateName, 'message' => $e->getMessage(), 'exception' => $e]
                );
                continue;
            }

            foreach ($accessEntries as $accessEntry) {
                if ($accessEntry->accessType === TemplateAccessEntry::ACCESS_TYPE_METHOD) {
                    if (!str_starts_with($accessEntry->attributeName, 'get')) {
                        continue;
                    }

                    $attributeName = lcfirst(substr($accessEntry->attributeName, 3));
                } elseif ($accessEntry->accessType === TemplateAccessEntry::ACCESS_TYPE_PROPERTY) {
                    $attributeName = $accessEntry->attributeName;
                } else {
                    continue;
                }

                $key = sprintf('%s::%s', $accessEntry->className, $attributeName);
                $entityFields[$key] = [
                    'entity' => $accessEntry->className,
                    'field' => $attributeName,
                ];
            }
        }

        return $entityFields;
    }

    /**
     * @param array<string, string> $variableTypes
     *
     * @return array<string, string>
     */
    private function dispatchBeforeEvent(EmailTemplate $emailTemplate, array $variableTypes): array
    {
        $event = new EmailTemplateSecurityPolicyCheckBefore(
            emailTemplate: $emailTemplate,
            variableTypes: $variableTypes
        );
        $this->eventDispatcher->dispatch($event);

        return $event->getVariableTypes();
    }
}
