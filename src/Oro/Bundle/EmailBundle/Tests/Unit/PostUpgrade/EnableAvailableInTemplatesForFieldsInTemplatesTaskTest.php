<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\PostUpgrade;

use Oro\Bundle\EmailBundle\PostUpgrade\EnableAvailableInTemplatesForFieldsInTemplatesTask;
use Oro\Bundle\EmailBundle\PostUpgrade\EntityFieldsUsedInEmailTemplatesProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class EnableAvailableInTemplatesForFieldsInTemplatesTaskTest extends TestCase
{
    private EntityFieldsUsedInEmailTemplatesProvider&MockObject $entityFieldsUsedInEmailTemplatesProvider;
    private EntityConfigManager&MockObject $entityConfigManager;
    private ConfigProvider&MockObject $configProvider;
    private EnableAvailableInTemplatesForFieldsInTemplatesTask $task;
    private InputInterface&MockObject $input;
    private OutputInterface&MockObject $output;
    private SymfonyStyle&MockObject $io;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityFieldsUsedInEmailTemplatesProvider = $this->createMock(
            EntityFieldsUsedInEmailTemplatesProvider::class
        );
        $this->entityConfigManager = $this->createMock(EntityConfigManager::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->entityConfigManager
            ->method('getProvider')
            ->with('email')
            ->willReturn($this->configProvider);

        $this->task = new EnableAvailableInTemplatesForFieldsInTemplatesTask(
            $this->entityFieldsUsedInEmailTemplatesProvider,
            $this->entityConfigManager
        );

        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
        $this->io = $this->createMock(SymfonyStyle::class);
    }

    public function testGetName(): void
    {
        self::assertSame(
            'enable_available_in_template_for_fields_in_templates',
            $this->task->getName()
        );
    }

    public function testGetDescription(): void
    {
        self::assertSame(
            'Ensures that "email.available_in_template" entity field config setting is enabled for fields that '
            . 'already present in email templates.',
            $this->task->getDescription()
        );
    }

    public function testExecuteWhenNoEntityFieldsUsedInEmailTemplates(): void
    {
        $this->entityFieldsUsedInEmailTemplatesProvider
            ->expects(self::once())
            ->method('getEntityFieldsUsedInEmailTemplates')
            ->willReturn([]);

        $this->configProvider
            ->expects(self::never())
            ->method('hasConfig');

        $this->configProvider
            ->expects(self::never())
            ->method('getConfig');

        $this->entityConfigManager
            ->expects(self::never())
            ->method('persist');

        $this->entityConfigManager
            ->expects(self::never())
            ->method('flush');

        $result = $this->task->execute($this->input, $this->output, $this->io);

        self::assertSame(true, $result->isExecuted());
        self::assertSame('enable_available_in_template_for_fields_in_templates', $result->getTaskName());
        self::assertSame(
            'All entity fields present in email templates were already enabled for use.',
            $result->getMessage()
        );
    }

    public function testExecuteEnablesAvailableInTemplateForFieldNotYetEnabled(): void
    {
        $entityClass = \stdClass::class;
        $fieldName = 'name';

        $this->entityFieldsUsedInEmailTemplatesProvider
            ->expects(self::once())
            ->method('getEntityFieldsUsedInEmailTemplates')
            ->willReturn([
                0 => ['entity' => $entityClass, 'field' => $fieldName],
            ]);

        $this->configProvider
            ->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass, $fieldName)
            ->willReturn(true);

        $entityFieldConfig = $this->createMock(ConfigInterface::class);
        $entityFieldConfig
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(false);
        $entityFieldConfig
            ->expects(self::once())
            ->method('set')
            ->with('available_in_template', true);

        $this->configProvider
            ->expects(self::once())
            ->method('getConfig')
            ->with($entityClass, $fieldName)
            ->willReturn($entityFieldConfig);

        $this->entityConfigManager
            ->expects(self::once())
            ->method('persist')
            ->with($entityFieldConfig);

        $this->entityConfigManager
            ->expects(self::once())
            ->method('flush');

        $result = $this->task->execute($this->input, $this->output, $this->io);

        self::assertSame(true, $result->isExecuted());
        self::assertSame(
            '1 entity fields are now enabled for use in email templates: stdClass::name',
            $result->getMessage()
        );
    }

    public function testExecuteSkipsFieldAlreadyEnabledForAvailableInTemplate(): void
    {
        $entityClass = \stdClass::class;
        $fieldName = 'name';

        $this->entityFieldsUsedInEmailTemplatesProvider
            ->expects(self::once())
            ->method('getEntityFieldsUsedInEmailTemplates')
            ->willReturn([
                0 => ['entity' => $entityClass, 'field' => $fieldName],
            ]);

        $this->configProvider
            ->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass, $fieldName)
            ->willReturn(true);

        $entityFieldConfig = $this->createMock(ConfigInterface::class);
        $entityFieldConfig
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(true);
        $entityFieldConfig
            ->expects(self::never())
            ->method('set');

        $this->configProvider
            ->expects(self::once())
            ->method('getConfig')
            ->with($entityClass, $fieldName)
            ->willReturn($entityFieldConfig);

        $this->entityConfigManager
            ->expects(self::never())
            ->method('persist');

        $this->entityConfigManager
            ->expects(self::never())
            ->method('flush');

        $result = $this->task->execute($this->input, $this->output, $this->io);

        self::assertSame(true, $result->isExecuted());
        self::assertSame(
            'All entity fields present in email templates were already enabled for use.',
            $result->getMessage()
        );
    }

    public function testExecuteSkipsFieldWhenEntityConfigEntryDoesNotExist(): void
    {
        $entityClass = \stdClass::class;
        $fieldName = 'name';

        $this->entityFieldsUsedInEmailTemplatesProvider
            ->expects(self::once())
            ->method('getEntityFieldsUsedInEmailTemplates')
            ->willReturn([
                0 => ['entity' => $entityClass, 'field' => $fieldName],
            ]);

        $this->configProvider
            ->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass, $fieldName)
            ->willReturn(false);

        $this->configProvider
            ->expects(self::never())
            ->method('getConfig');

        $this->entityConfigManager
            ->expects(self::never())
            ->method('persist');

        $this->entityConfigManager
            ->expects(self::never())
            ->method('flush');

        $result = $this->task->execute($this->input, $this->output, $this->io);

        self::assertSame(true, $result->isExecuted());
        self::assertSame(
            'All entity fields present in email templates were already enabled for use.',
            $result->getMessage()
        );
    }

    public function testExecuteEnablesMultipleFieldsAndFlushesOnce(): void
    {
        $entityClass = \stdClass::class;
        $fieldName1 = 'firstName';
        $fieldName2 = 'lastName';

        $this->entityFieldsUsedInEmailTemplatesProvider
            ->expects(self::once())
            ->method('getEntityFieldsUsedInEmailTemplates')
            ->willReturn([
                0 => ['entity' => $entityClass, 'field' => $fieldName1],
                1 => ['entity' => $entityClass, 'field' => $fieldName2],
            ]);

        $this->configProvider
            ->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap([
                [$entityClass, $fieldName1, true],
                [$entityClass, $fieldName2, true],
            ]);

        $entityFieldConfig1 = $this->createMock(ConfigInterface::class);
        $entityFieldConfig1
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(false);
        $entityFieldConfig1
            ->expects(self::once())
            ->method('set')
            ->with('available_in_template', true);

        $entityFieldConfig2 = $this->createMock(ConfigInterface::class);
        $entityFieldConfig2
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(false);
        $entityFieldConfig2
            ->expects(self::once())
            ->method('set')
            ->with('available_in_template', true);

        $this->configProvider
            ->expects(self::exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [$entityClass, $fieldName1, $entityFieldConfig1],
                [$entityClass, $fieldName2, $entityFieldConfig2],
            ]);

        $this->entityConfigManager
            ->expects(self::exactly(2))
            ->method('persist');

        $this->entityConfigManager
            ->expects(self::once())
            ->method('flush');

        $result = $this->task->execute($this->input, $this->output, $this->io);

        self::assertSame(true, $result->isExecuted());
        self::assertSame(
            '2 entity fields are now enabled for use in email templates: stdClass::firstName, stdClass::lastName',
            $result->getMessage()
        );
    }

    public function testExecutePartiallyEnablesFieldsWhenSomeAlreadyEnabled(): void
    {
        $entityClass = \stdClass::class;
        $fieldName1 = 'firstName';
        $fieldName2 = 'lastName';

        $this->entityFieldsUsedInEmailTemplatesProvider
            ->expects(self::once())
            ->method('getEntityFieldsUsedInEmailTemplates')
            ->willReturn([
                0 => ['entity' => $entityClass, 'field' => $fieldName1],
                1 => ['entity' => $entityClass, 'field' => $fieldName2],
            ]);

        $this->configProvider
            ->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap([
                [$entityClass, $fieldName1, true],
                [$entityClass, $fieldName2, true],
            ]);

        $entityFieldConfig1 = $this->createMock(ConfigInterface::class);
        $entityFieldConfig1
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(true);
        $entityFieldConfig1
            ->expects(self::never())
            ->method('set');

        $entityFieldConfig2 = $this->createMock(ConfigInterface::class);
        $entityFieldConfig2
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(false);
        $entityFieldConfig2
            ->expects(self::once())
            ->method('set')
            ->with('available_in_template', true);

        $this->configProvider
            ->expects(self::exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [$entityClass, $fieldName1, $entityFieldConfig1],
                [$entityClass, $fieldName2, $entityFieldConfig2],
            ]);

        $this->entityConfigManager
            ->expects(self::once())
            ->method('persist')
            ->with($entityFieldConfig2);

        $this->entityConfigManager
            ->expects(self::once())
            ->method('flush');

        $result = $this->task->execute($this->input, $this->output, $this->io);

        self::assertSame(true, $result->isExecuted());
        self::assertSame(
            '1 entity fields are now enabled for use in email templates: stdClass::lastName',
            $result->getMessage()
        );
    }

    public function testExecuteDoesEnableFieldWhenConfigValueIsNullInsteadOfTrue(): void
    {
        $entityClass = \stdClass::class;
        $fieldName = 'name';

        $this->entityFieldsUsedInEmailTemplatesProvider
            ->expects(self::once())
            ->method('getEntityFieldsUsedInEmailTemplates')
            ->willReturn([
                0 => ['entity' => $entityClass, 'field' => $fieldName],
            ]);

        $this->configProvider
            ->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass, $fieldName)
            ->willReturn(true);

        $entityFieldConfig = $this->createMock(ConfigInterface::class);
        $entityFieldConfig
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(null);
        $entityFieldConfig
            ->expects(self::once())
            ->method('set')
            ->with('available_in_template', true);

        $this->configProvider
            ->expects(self::once())
            ->method('getConfig')
            ->with($entityClass, $fieldName)
            ->willReturn($entityFieldConfig);

        $this->entityConfigManager
            ->expects(self::once())
            ->method('persist')
            ->with($entityFieldConfig);

        $this->entityConfigManager
            ->expects(self::once())
            ->method('flush');

        $result = $this->task->execute($this->input, $this->output, $this->io);

        self::assertSame(true, $result->isExecuted());
        self::assertSame(
            '1 entity fields are now enabled for use in email templates: stdClass::name',
            $result->getMessage()
        );
    }

    public function testExecuteSkipsFieldWhenEntityConfigEntryDoesNotExistAndEnablesOtherFields(): void
    {
        $entityClass = \stdClass::class;
        $fieldNameWithoutConfig = 'orphanField';
        $fieldNameWithConfig = 'name';

        $this->entityFieldsUsedInEmailTemplatesProvider
            ->expects(self::once())
            ->method('getEntityFieldsUsedInEmailTemplates')
            ->willReturn([
                0 => ['entity' => $entityClass, 'field' => $fieldNameWithoutConfig],
                1 => ['entity' => $entityClass, 'field' => $fieldNameWithConfig],
            ]);

        $this->configProvider
            ->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap([
                [$entityClass, $fieldNameWithoutConfig, false],
                [$entityClass, $fieldNameWithConfig, true],
            ]);

        $entityFieldConfig = $this->createMock(ConfigInterface::class);
        $entityFieldConfig
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(false);
        $entityFieldConfig
            ->expects(self::once())
            ->method('set')
            ->with('available_in_template', true);

        $this->configProvider
            ->expects(self::once())
            ->method('getConfig')
            ->with($entityClass, $fieldNameWithConfig)
            ->willReturn($entityFieldConfig);

        $this->entityConfigManager
            ->expects(self::once())
            ->method('persist')
            ->with($entityFieldConfig);

        $this->entityConfigManager
            ->expects(self::once())
            ->method('flush');

        $result = $this->task->execute($this->input, $this->output, $this->io);

        self::assertSame(true, $result->isExecuted());
        self::assertSame(
            '1 entity fields are now enabled for use in email templates: stdClass::name',
            $result->getMessage()
        );
    }
}
