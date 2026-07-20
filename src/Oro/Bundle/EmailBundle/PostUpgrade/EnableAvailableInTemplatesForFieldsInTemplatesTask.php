<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\PostUpgrade;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\PlatformBundle\PostUpgrade\PostUpgradeTaskInterface;
use Oro\Bundle\PlatformBundle\PostUpgrade\PostUpgradeTaskResult;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Ensures that "email.available_in_template" entity field config setting is enabled for fields that already present
 * in email templates.
 */
final class EnableAvailableInTemplatesForFieldsInTemplatesTask implements PostUpgradeTaskInterface
{
    public function __construct(
        private readonly EntityFieldsUsedInEmailTemplatesProvider $entityFieldsUsedInEmailTemplatesProvider,
        private readonly EntityConfigManager $entityConfigManager,
    ) {
    }

    #[\Override]
    public function getName(): string
    {
        return 'enable_available_in_template_for_fields_in_templates';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Ensures that "email.available_in_template" entity field config setting is enabled for fields that '
            . 'already present in email templates.';
    }

    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output, SymfonyStyle $io): PostUpgradeTaskResult
    {
        $entityFields = $this->entityFieldsUsedInEmailTemplatesProvider->getEntityFieldsUsedInEmailTemplates();
        $configProvider = $this->entityConfigManager->getProvider('email');

        $updatedEntityFields = [];
        foreach ($entityFields as $entityField) {
            if (!$configProvider->hasConfig($entityField['entity'], $entityField['field'])) {
                continue;
            }

            $entityFieldConfig = $configProvider->getConfig($entityField['entity'], $entityField['field']);
            if ($entityFieldConfig->get('available_in_template') !== true) {
                $entityFieldConfig->set('available_in_template', true);

                $this->entityConfigManager->persist($entityFieldConfig);
                $updatedEntityFields[] = $entityField['entity'] . '::' . $entityField['field'];
            }
        }

        if ($updatedEntityFields) {
            $this->entityConfigManager->flush();

            $message = sprintf(
                '%d entity fields are now enabled for use in email templates: %s',
                count($updatedEntityFields),
                implode(', ', $updatedEntityFields)
            );
        } else {
            $message = 'All entity fields present in email templates were already enabled for use.';
        }

        return new PostUpgradeTaskResult(
            taskName: $this->getName(),
            executed: true,
            message: $message
        );
    }
}
