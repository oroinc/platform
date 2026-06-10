<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\PostUpgrade;

use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailTemplateForEntityAvailableInTemplateTask;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
final class EnableAvailableInTemplateForEntitiesHavingTemplatesTaskTest extends WebTestCase
{
    private ConfigManager $configManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadEmailTemplateForEntityAvailableInTemplateTask::class]);

        $this->configManager = self::getContainer()->get('oro_entity_config.config_manager');
    }

    public function testTaskEnablesEntityThatWasNotAvailableInTemplate(): void
    {
        $emailConfigProvider = $this->configManager->getProvider('email');
        $entityConfig = $emailConfigProvider->getConfig(User::class);
        $entityConfig->set('available_in_template', false);
        $this->configManager->persist($entityConfig);
        $this->configManager->flush();

        $commandOutput = self::runCommand(
            'oro:platform:post-upgrade-tasks',
            ['--task' => 'enable_available_in_template_for_entities_having_templates']
        );

        self::assertStringContainsString(
            'enable_available_in_template_for_entities_having_templates',
            $commandOutput
        );
        self::assertStringContainsString(
            '1 entities are now available when creating email templates',
            $commandOutput
        );
        self::assertStringContainsString(User::class, $commandOutput);

        $this->configManager->clear();

        $refreshedEntityConfig = $emailConfigProvider->getConfig(User::class);
        self::assertTrue(
            $refreshedEntityConfig->get('available_in_template'),
            'User entity available_in_template must be true after the task runs.'
        );
    }

    public function testTaskSkipsEntityAlreadyAvailableInTemplate(): void
    {
        $emailConfigProvider = $this->configManager->getProvider('email');
        $entityConfig = $emailConfigProvider->getConfig(User::class);
        $entityConfig->set('available_in_template', true);
        $this->configManager->persist($entityConfig);
        $this->configManager->flush();

        $commandOutput = self::runCommand(
            'oro:platform:post-upgrade-tasks',
            ['--task' => 'enable_available_in_template_for_entities_having_templates']
        );

        self::assertStringContainsString(
            'All entities used in email templates were already available when creating email templates.',
            $commandOutput
        );

        $this->configManager->clear();

        $refreshedEntityConfig = $emailConfigProvider->getConfig(User::class);
        self::assertTrue(
            $refreshedEntityConfig->get('available_in_template'),
            'User entity available_in_template must remain true after the task skips it.'
        );
    }
}
