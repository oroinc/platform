<?php

namespace Oro\Bundle\NotificationBundle\Command;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\NotificationBundle\Entity\NotificationAlert;
use Oro\Bundle\NotificationBundle\Exception\NotificationAlertFetchFailedException;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Lists notification alert records.
 */
class NotificationAlertListCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:notification:alerts:list';

    private ManagerRegistry $registry;
    private TokenAccessor $tokenAccessor;
    private DateTimeFormatterInterface $dateTimeFormatter;
    private ?QueryBuilder $queryBuilder = null;

    public function __construct(
        ManagerRegistry $registry,
        TokenAccessor $tokenAccessor,
        DateTimeFormatterInterface $dateTimeFormatter
    ) {
        parent::__construct();

        $this->registry = $registry;
        $this->tokenAccessor = $tokenAccessor;
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    protected function configure()
    {
        $this
            ->addOption('per-page', null, InputOption::VALUE_REQUIRED, 'Page size of the result set', 20)
            ->addOption('page', null, InputOption::VALUE_REQUIRED, 'Page of the result set', 1)
            ->addOption('source-type', null, InputOption::VALUE_OPTIONAL, 'Filter by source type')
            ->addOption('resource-type', null, InputOption::VALUE_OPTIONAL, 'Filter by resource type')
            ->addOption('alert-type', null, InputOption::VALUE_OPTIONAL, 'Filter by alert type')
            ->addOption('summary', null, InputOption::VALUE_NONE, 'Group alerts by source, resource, alert type.')
            ->addOption('resolved', null, InputOption::VALUE_NONE, 'Include resolved notification alerts')
            ->setDescription('Lists notification alert records for given user and organization.')
            ->addUsage('--current-user=<user-identifier> --current-organization=<organization-identifier>')
            ->setHelp(
                <<<HELP
The <info>%command.name%</info> command lists notification alerts records
    -- when `summary` options is provided the result will be grouped by `Source Type`, `Resource Type` and `Alert Type`
    -- when `--current-user`, `--current-organization`, `--source-type', `--resource-type` or `--alert-type`
       options are provided the appropriate filters will be applied against results 
    -- by default only not resolved notification alerts are shown,
       this behaviour can be changed by using `--resolved` option

  <info>php %command.full_name%</info>
  <info>php %command.full_name% --resource-type=calendar</info>
  <info>php %command.full_name% --resource-type=calendar --summary</info>
  <info>php %command.full_name% --resource-type=calendar --resolved</info>
  <info>php %command.full_name% --resource-type=calendar --alert-type=auth</info>
  <info>php %command.full_name% --source-type="My Integration" --resource-type=calendar --alert-type=sync</info>
  <info>php %command.full_name% --current-user=<user-identifier></info>
  <info>php %command.full_name% --current-user=<user-identifier> --current-organization=<organization-identifier></info>
  <info>php %command.full_name% --current-user=admin --current-organization=Oro</info>
  <info>php %command.full_name% --current-user=1 --current-organization=1</info>

HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $builder = $this->getQueryBuilder();

        $this->processFilters($input);
        $this->processPagination($input);

        if ($input->getOption('summary')) {
            $tableHeader = ['Source', 'Resource', 'Alert Type', 'Alerts Amount'];
            $builder
                ->select('a.sourceType, a.resourceType, a.alertType, COUNT(a.id) as cnt')
                ->groupBy('a.resourceType, a.alertType, a.sourceType')
                ->orderBy('COUNT(a.id)', 'DESC');
        } else {
            $tableHeader = [
                'Id',
                'Created At',
                'Updated At',
                'Username',
                'Source',
                'Resource',
                'Alert Type',
                'Operation',
                'Step',
                'Item Id',
                'External Id',
                'Message',
                'Resolved',
            ];
            $builder
                ->select('a.id, a.createdAt, a.updatedAt, u.username as user, a.sourceType, a.resourceType')
                ->addSelect('a.alertType, a.operation, a.step, a.itemId, a.externalId, a.message, a.resolved')
                ->innerJoin('OroUserBundle:User', 'u', 'WITH', 'u.id = a.user');
        }

        try {
            $rows = $builder->getQuery()->getArrayResult();
        } catch (\Exception $e) {
            throw new NotificationAlertFetchFailedException('Failed to list notification alerts.', $e->getCode(), $e);
        }

        if (0 !== count($rows)) {
            $table = new Table($output);
            $table
                ->setHeaders($tableHeader)
                ->setRows(array_map([$this, 'getRow'], $rows))
                ->setColumnMaxWidth(10, 31) // limit ExternalId column width
                ->setColumnMaxWidth(11, 31) // limit Message column width
                ->render();
        } else {
            $io = new SymfonyStyle($input, $output);
            $io->text('<info>There are no notification alerts.</info>');
        }

        return 0;
    }

    private function isSummaryOutput(InputInterface $input): bool
    {
        return
            $input->getOption('summary')
            || (
                empty($input->getOption('source-type'))
                && empty($input->getOption('resource-type'))
                && empty($input->getOption('alert-type'))
                && !$this->tokenAccessor->getUserId()
                && !$this->tokenAccessor->getOrganizationId()
            );
    }

    private function getRow($row): array
    {
        if (isset($row['createdAt'])) {
            $row['createdAt'] = $this->dateTimeFormatter->format(
                $row['createdAt'],
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::SHORT
            );
        }
        if (isset($row['updatedAt'])) {
            $row['updatedAt'] = $this->dateTimeFormatter->format(
                $row['updatedAt'],
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::SHORT
            );
        }
        if (isset($row['resolved'])) {
            $row['resolved'] = $row['resolved'] ? 'Yes' : 'No';
        }

        return $row;
    }

    private function processFilters(InputInterface $input): void
    {
        $builder = $this->getQueryBuilder();

        $sourceType = $input->getOption('source-type');
        if (!empty($sourceType)) {
            $builder
                ->andWhere('a.sourceType = :sourceType')
                ->setParameter('sourceType', $sourceType);
        }

        $resourceType = $input->getOption('resource-type');
        if (!empty($resourceType)) {
            $builder
                ->andWhere('a.resourceType = :resourceType')
                ->setParameter('resourceType', $resourceType);
        }

        $alertType = $input->getOption('alert-type');
        if (!empty($alertType)) {
            $builder
                ->andWhere('a.alertType = :alertType')
                ->setParameter('alertType', $alertType);
        }

        $includeResolved = $input->getOption('resolved');
        if (!$includeResolved) {
            $builder
                ->andWhere('a.resolved = :resolved')
                ->setParameter('resolved', false);
        }

        $currentUser = $this->tokenAccessor->getUserId();
        if ($currentUser) {
            $builder
                ->andWhere('a.user = :user')
                ->setParameter('user', $currentUser);
        }

        $currentOrganization = $this->tokenAccessor->getOrganizationId();
        if ($currentOrganization) {
            $builder
                ->andWhere('a.organization = :organization')
                ->setParameter('organization', $currentOrganization);
        }
    }

    private function processPagination(InputInterface $input): void
    {
        $limit = (int) $input->getOption('per-page');
        $offset = ((int) $input->getOption('page') - 1) * $limit;

        $builder = $this->getQueryBuilder();
        $builder
            ->setFirstResult($offset)
            ->setMaxResults($limit);
    }

    private function getQueryBuilder(): QueryBuilder
    {
        if (null === $this->queryBuilder) {
            /** @var EntityRepository $entityRepository */
            $entityRepository = $this->registry->getRepository(NotificationAlert::class);

            $this->queryBuilder = $entityRepository->createQueryBuilder('a');
        }

        return $this->queryBuilder;
    }
}
