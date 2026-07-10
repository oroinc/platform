<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\PostUpgrade;

use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailTemplateForFieldAvailableInTemplateTask;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
final class EnableAvailableInTemplatesForFieldsInTemplatesTaskTest extends WebTestCase
{
    private ConfigManager $configManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadEmailTemplateForFieldAvailableInTemplateTask::class]);

        $this->configManager = self::getContainer()->get('oro_entity_config.config_manager');
    }

    public function testTaskEnablesFieldsThatWereNotAvailableInTemplate(): void
    {
        $emailConfigProvider = $this->configManager->getProvider('email');

        $firstNameFieldConfig = $emailConfigProvider->getConfig(User::class, 'firstName');
        $firstNameFieldConfig->set('available_in_template', false);
        $this->configManager->persist($firstNameFieldConfig);

        $lastNameFieldConfig = $emailConfigProvider->getConfig(User::class, 'lastName');
        $lastNameFieldConfig->set('available_in_template', false);
        $this->configManager->persist($lastNameFieldConfig);

        $this->configManager->flush();

        $commandOutput = self::runCommand(
            'oro:platform:post-upgrade-tasks',
            ['--task' => 'enable_available_in_template_for_fields_in_templates']
        );

        self::assertStringContainsString(
            'enable_available_in_template_for_fields_in_templates',
            $commandOutput
        );
        self::assertStringContainsString(
            '2 entity fields are now enabled for use in email templates',
            $commandOutput
        );

        $this->configManager->clear();

        $refreshedFirstNameConfig = $emailConfigProvider->getConfig(User::class, 'firstName');
        self::assertTrue(
            $refreshedFirstNameConfig->get('available_in_template'),
            'User.firstName available_in_template must be true after the task runs.'
        );

        $refreshedLastNameConfig = $emailConfigProvider->getConfig(User::class, 'lastName');
        self::assertTrue(
            $refreshedLastNameConfig->get('available_in_template'),
            'User.lastName available_in_template must be true after the task runs.'
        );
    }

    public function testTaskSkipsFieldsAlreadyAvailableInTemplate(): void
    {
        $emailConfigProvider = $this->configManager->getProvider('email');

        $firstNameFieldConfig = $emailConfigProvider->getConfig(User::class, 'firstName');
        $firstNameFieldConfig->set('available_in_template', true);
        $this->configManager->persist($firstNameFieldConfig);

        $lastNameFieldConfig = $emailConfigProvider->getConfig(User::class, 'lastName');
        $lastNameFieldConfig->set('available_in_template', true);
        $this->configManager->persist($lastNameFieldConfig);

        $this->configManager->flush();

        $commandOutput = self::runCommand(
            'oro:platform:post-upgrade-tasks',
            ['--task' => 'enable_available_in_template_for_fields_in_templates']
        );

        self::assertStringContainsString(
            'All entity fields present in email templates were already enabled for use.',
            $commandOutput
        );

        $this->configManager->clear();

        $refreshedFirstNameConfig = $emailConfigProvider->getConfig(User::class, 'firstName');
        self::assertTrue(
            $refreshedFirstNameConfig->get('available_in_template'),
            'User.firstName available_in_template must remain true after the task skips it.'
        );

        $refreshedLastNameConfig = $emailConfigProvider->getConfig(User::class, 'lastName');
        self::assertTrue(
            $refreshedLastNameConfig->get('available_in_template'),
            'User.lastName available_in_template must remain true after the task skips it.'
        );
    }

    public function testTaskEnablesOnlyFieldsThatWereNotAvailableInTemplate(): void
    {
        $emailConfigProvider = $this->configManager->getProvider('email');

        $firstNameFieldConfig = $emailConfigProvider->getConfig(User::class, 'firstName');
        $firstNameFieldConfig->set('available_in_template', true);
        $this->configManager->persist($firstNameFieldConfig);

        $lastNameFieldConfig = $emailConfigProvider->getConfig(User::class, 'lastName');
        $lastNameFieldConfig->set('available_in_template', false);
        $this->configManager->persist($lastNameFieldConfig);

        $this->configManager->flush();

        $commandOutput = self::runCommand(
            'oro:platform:post-upgrade-tasks',
            ['--task' => 'enable_available_in_template_for_fields_in_templates']
        );

        self::assertStringContainsString(
            '1 entity fields are now enabled for use in email templates',
            $commandOutput
        );
        self::assertStringContainsString(
            User::class . '::lastName',
            $commandOutput
        );

        $this->configManager->clear();

        $refreshedFirstNameConfig = $emailConfigProvider->getConfig(User::class, 'firstName');
        self::assertTrue(
            $refreshedFirstNameConfig->get('available_in_template'),
            'User.firstName available_in_template must remain true after the task runs.'
        );

        $refreshedLastNameConfig = $emailConfigProvider->getConfig(User::class, 'lastName');
        self::assertTrue(
            $refreshedLastNameConfig->get('available_in_template'),
            'User.lastName available_in_template must be true after the task enables it.'
        );
    }
}
