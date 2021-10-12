<?php

namespace Oro\Bundle\NotificationBundle\Command;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\Entity\NotificationAlert;
use Oro\Bundle\NotificationBundle\Exception\NotificationAlertUpdateFailedException;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Deletes notification alert records.
 */
class NotificationAlertCleanupCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:notification:alerts:cleanup';

    private ManagerRegistry $registry;
    private TokenAccessor $tokenAccessor;
    private ?QueryBuilder $queryBuilder = null;

    public function __construct(
        ManagerRegistry $registry,
        TokenAccessor $tokenAccessor
    ) {
        parent::__construct();

        $this->registry = $registry;
        $this->tokenAccessor = $tokenAccessor;
    }

    protected function configure()
    {
        $this
            ->setDescription('Deletes notification alert records.')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Delete by Id')
            ->addOption('source-type', null, InputOption::VALUE_OPTIONAL, 'Delete by source type')
            ->addOption('resource-type', null, InputOption::VALUE_OPTIONAL, 'Delete by resource type')
            ->addOption('alert-type', null, InputOption::VALUE_OPTIONAL, 'Delete by alert type')
            ->addUsage('--current-user=<user-identifier> --current-organization=<organization-identifier>')
            ->setHelp(
                <<<HELP
The <info>%command.name%</info> command deletes notification alert records
    -- when no options are provided all notification alert record will be deleted
    -- when `id`, `--source-type', `--resource-type`, `--alert-type`, `--current-user`, `--current-organization`
       options are provided the appropriate filters will be applied for deletion 

  <info>php %command.full_name%</info>
  <info>php %command.full_name% --id=aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee</info>
  <info>php %command.full_name% --resource-type=calendar</info>
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
        $io = new SymfonyStyle($input, $output);

        $deleteAll = $this->isDeleteAll($input);
        if ($deleteAll) {
            $deleteAllConfirmation = $this->confirmDeleteAll($input, $io);
            if (!$deleteAllConfirmation) {
                return;
            }
        } else {
            $this->processFilters($input);
        }

        try {
            $deletedRows = $this->getQueryBuilder()->delete()->getQuery()->execute();
        } catch (\Exception $e) {
            throw new NotificationAlertUpdateFailedException(
                'Failed to delete notification alerts.',
                $e->getCode(),
                $e
            );
        }

        if ($deletedRows > 0) {
            $io->text(sprintf('<info>%d notification alert(s) was successfully deleted.', $deletedRows));
        } else {
            $io->text('<info>There are no notification alerts.</info>');
        }

        return 0;
    }

    private function confirmDeleteAll(InputInterface $input, SymfonyStyle $io): bool
    {
        $confirmation = true;
        if ($input->isInteractive()) {
            $io->warning('You are about to delete all notification alerts.');
            $confirmation = $io->askQuestion(
                new ConfirmationQuestion('WARNING! Are you sure you wish to continue?')
            );
        }

        if (!$confirmation) {
            $io->warning('Action cancelled!');
        }

        return $confirmation;
    }

    private function isDeleteAll(InputInterface $input): bool
    {
        return
            empty($input->getOption('source-type'))
            && empty($input->getOption('resource-type'))
            && empty($input->getOption('alert-type'))
            && empty($input->getOption('id'))
            && !$this->tokenAccessor->getUserId()
            && !$this->tokenAccessor->getOrganizationId();
    }

    private function processFilters(InputInterface $input): void
    {
        $builder = $this->getQueryBuilder();

        $id = $input->getOption('id');
        if (!empty($id)) {
            $builder
                ->andWhere('a.id = :id')
                ->setParameter('id', $id);
        }

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
