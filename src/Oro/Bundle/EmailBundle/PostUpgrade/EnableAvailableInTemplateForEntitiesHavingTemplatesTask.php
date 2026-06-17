<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\PostUpgrade;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\PlatformBundle\PostUpgrade\PostUpgradeTaskInterface;
use Oro\Bundle\PlatformBundle\PostUpgrade\PostUpgradeTaskResult;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Ensures that "email.available_in_template" entity config setting is enabled for entities that already have
 * email templates, i.e. being the root entity of an email template.
 */
final class EnableAvailableInTemplateForEntitiesHavingTemplatesTask implements PostUpgradeTaskInterface
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly EntityConfigManager $entityConfigManager,
    ) {
    }

    #[\Override]
    public function getName(): string
    {
        return 'enable_available_in_template_for_entities_having_templates';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Ensures that "email.available_in_template" entity config setting is enabled for entities that '
            . 'already have email templates, i.e. being the root entity of an email template.';
    }

    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output, SymfonyStyle $io): PostUpgradeTaskResult
    {
        $entityClasses = $this->getEntityClassesHavingEmailTemplates();
        $configProvider = $this->entityConfigManager->getProvider('email');

        $updatedEntityClasses = [];
        foreach ($entityClasses as $entityClass) {
            $entityConfig = $configProvider->getConfig($entityClass);
            if ($entityConfig->get('available_in_template') !== true) {
                $entityConfig->set('available_in_template', true);

                $this->entityConfigManager->persist($entityConfig);
                $updatedEntityClasses[] = $entityClass;
            }
        }

        if ($updatedEntityClasses) {
            $this->entityConfigManager->flush();

            $message = sprintf(
                '%d entities are now available when creating email templates: %s',
                count($updatedEntityClasses),
                implode(', ', $updatedEntityClasses)
            );
        } else {
            $message = 'All entities used in email templates were already available when creating email templates.';
        }

        return new PostUpgradeTaskResult(
            taskName: $this->getName(),
            executed: true,
            message: $message
        );
    }

    private function getEntityClassesHavingEmailTemplates(): array
    {
        $emailTemplateRepository = $this->doctrine->getRepository(EmailTemplate::class);

        return $emailTemplateRepository
            ->getDistinctByEntityNameQueryBuilder()
            ->getQuery()
            ->getSingleColumnResult();
    }
}
