<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\PostUpgrade;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\PostUpgrade\EnableAvailableInTemplateForEntitiesHavingTemplatesTask;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class EnableAvailableInTemplateForEntitiesHavingTemplatesTaskTest extends TestCase
{
    private EntityConfigManager&MockObject $entityConfigManager;
    private EnableAvailableInTemplateForEntitiesHavingTemplatesTask $task;
    private InputInterface&MockObject $input;
    private OutputInterface&MockObject $output;
    private SymfonyStyle&MockObject $io;
    private EmailTemplateRepository&MockObject $repository;
    private ConfigProvider&MockObject $configProvider;

    #[\Override]
    protected function setUp(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $this->entityConfigManager = $this->createMock(EntityConfigManager::class);
        $this->repository = $this->createMock(EmailTemplateRepository::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $doctrine
            ->method('getRepository')
            ->with(EmailTemplate::class)
            ->willReturn($this->repository);

        $this->entityConfigManager
            ->method('getProvider')
            ->with('email')
            ->willReturn($this->configProvider);

        $this->task = new EnableAvailableInTemplateForEntitiesHavingTemplatesTask(
            $doctrine,
            $this->entityConfigManager
        );

        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
        $this->io = $this->createMock(SymfonyStyle::class);
    }

    public function testGetName(): void
    {
        self::assertSame(
            'enable_available_in_template_for_entities_having_templates',
            $this->task->getName()
        );
    }

    public function testGetDescription(): void
    {
        self::assertSame(
            'Ensures that "email.available_in_template" entity config setting is enabled for entities that '
            . 'already have email templates, i.e. being the root entity of an email template.',
            $this->task->getDescription()
        );
    }

    public function testExecuteWhenNoEntityClassesHavingEmailTemplates(): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $query
            ->expects(self::once())
            ->method('getSingleColumnResult')
            ->willReturn([]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects(self::once())
            ->method('getDistinctByEntityNameQueryBuilder')
            ->willReturn($queryBuilder);

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
        self::assertSame('enable_available_in_template_for_entities_having_templates', $result->getTaskName());
        self::assertSame(
            'All entities used in email templates were already available when creating email templates.',
            $result->getMessage()
        );
    }

    public function testExecuteEnablesAvailableInTemplateForEntityNotYetEnabled(): void
    {
        $entityClass = \stdClass::class;

        $query = $this->createMock(AbstractQuery::class);
        $query
            ->expects(self::once())
            ->method('getSingleColumnResult')
            ->willReturn([$entityClass]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects(self::once())
            ->method('getDistinctByEntityNameQueryBuilder')
            ->willReturn($queryBuilder);

        $config = $this->createMock(ConfigInterface::class);
        $config
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(false);
        $config
            ->expects(self::once())
            ->method('set')
            ->with('available_in_template', true);

        $this->configProvider
            ->expects(self::once())
            ->method('getConfig')
            ->with($entityClass)
            ->willReturn($config);

        $this->entityConfigManager
            ->expects(self::once())
            ->method('persist')
            ->with($config);

        $this->entityConfigManager
            ->expects(self::once())
            ->method('flush');

        $result = $this->task->execute($this->input, $this->output, $this->io);

        self::assertSame(true, $result->isExecuted());
        self::assertSame(
            '1 entities are now available when creating email templates: stdClass',
            $result->getMessage()
        );
    }

    public function testExecuteSkipsEntityAlreadyEnabledForAvailableInTemplate(): void
    {
        $entityClass = \stdClass::class;

        $query = $this->createMock(AbstractQuery::class);
        $query
            ->expects(self::once())
            ->method('getSingleColumnResult')
            ->willReturn([$entityClass]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects(self::once())
            ->method('getDistinctByEntityNameQueryBuilder')
            ->willReturn($queryBuilder);

        $config = $this->createMock(ConfigInterface::class);
        $config
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(true);
        $config
            ->expects(self::never())
            ->method('set');

        $this->configProvider
            ->expects(self::once())
            ->method('getConfig')
            ->with($entityClass)
            ->willReturn($config);

        $this->entityConfigManager
            ->expects(self::never())
            ->method('persist');

        $this->entityConfigManager
            ->expects(self::never())
            ->method('flush');

        $result = $this->task->execute($this->input, $this->output, $this->io);

        self::assertSame(true, $result->isExecuted());
        self::assertSame(
            'All entities used in email templates were already available when creating email templates.',
            $result->getMessage()
        );
    }

    public function testExecuteEnablesMultipleEntitiesAndFlushesOnce(): void
    {
        $entityClass1 = \stdClass::class;
        $entityClass2 = \DateTime::class;

        $query = $this->createMock(AbstractQuery::class);
        $query
            ->expects(self::once())
            ->method('getSingleColumnResult')
            ->willReturn([$entityClass1, $entityClass2]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects(self::once())
            ->method('getDistinctByEntityNameQueryBuilder')
            ->willReturn($queryBuilder);

        $config1 = $this->createMock(ConfigInterface::class);
        $config1
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(false);
        $config1
            ->expects(self::once())
            ->method('set')
            ->with('available_in_template', true);

        $config2 = $this->createMock(ConfigInterface::class);
        $config2
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(false);
        $config2
            ->expects(self::once())
            ->method('set')
            ->with('available_in_template', true);

        $this->configProvider
            ->expects(self::exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [$entityClass1, null, $config1],
                [$entityClass2, null, $config2],
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
            '2 entities are now available when creating email templates: stdClass, DateTime',
            $result->getMessage()
        );
    }

    public function testExecutePartiallyEnablesEntitiesWhenSomeAlreadyEnabled(): void
    {
        $entityClass1 = \stdClass::class;
        $entityClass2 = \DateTime::class;

        $query = $this->createMock(AbstractQuery::class);
        $query
            ->expects(self::once())
            ->method('getSingleColumnResult')
            ->willReturn([$entityClass1, $entityClass2]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects(self::once())
            ->method('getDistinctByEntityNameQueryBuilder')
            ->willReturn($queryBuilder);

        $config1 = $this->createMock(ConfigInterface::class);
        $config1
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(true);
        $config1
            ->expects(self::never())
            ->method('set');

        $config2 = $this->createMock(ConfigInterface::class);
        $config2
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(false);
        $config2
            ->expects(self::once())
            ->method('set')
            ->with('available_in_template', true);

        $this->configProvider
            ->expects(self::exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [$entityClass1, null, $config1],
                [$entityClass2, null, $config2],
            ]);

        $this->entityConfigManager
            ->expects(self::once())
            ->method('persist')
            ->with($config2);

        $this->entityConfigManager
            ->expects(self::once())
            ->method('flush');

        $result = $this->task->execute($this->input, $this->output, $this->io);

        self::assertSame(true, $result->isExecuted());
        self::assertSame(
            '1 entities are now available when creating email templates: DateTime',
            $result->getMessage()
        );
    }

    public function testExecuteDoesEnableEntityWhenConfigValueIsNullInsteadOfTrue(): void
    {
        $entityClass = \stdClass::class;

        $query = $this->createMock(AbstractQuery::class);
        $query
            ->expects(self::once())
            ->method('getSingleColumnResult')
            ->willReturn([$entityClass]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects(self::once())
            ->method('getDistinctByEntityNameQueryBuilder')
            ->willReturn($queryBuilder);

        $config = $this->createMock(ConfigInterface::class);
        $config
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(null);
        $config
            ->expects(self::once())
            ->method('set')
            ->with('available_in_template', true);

        $this->configProvider
            ->expects(self::once())
            ->method('getConfig')
            ->with($entityClass)
            ->willReturn($config);

        $this->entityConfigManager
            ->expects(self::once())
            ->method('persist')
            ->with($config);

        $this->entityConfigManager
            ->expects(self::once())
            ->method('flush');

        $result = $this->task->execute($this->input, $this->output, $this->io);

        self::assertSame(true, $result->isExecuted());
        self::assertSame(
            '1 entities are now available when creating email templates: stdClass',
            $result->getMessage()
        );
    }
}
