<?php
declare(strict_types=1);

namespace Oro\Bundle\OrganizationBundle\Command;

use Oro\Bundle\OrganizationBundle\Entity\Manager\OrganizationManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Updates an organization.
 */
class UpdateOrganizationCommand extends Command
{
    protected static $defaultName = 'oro:organization:update';

    private OrganizationManager $organizationManager;

    public function __construct(OrganizationManager $organizationManager)
    {
        $this->organizationManager = $organizationManager;
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addArgument('organization-name', InputArgument::REQUIRED, 'Organization name')
            ->addOption('organization-name', null, InputOption::VALUE_OPTIONAL, 'New name')
            ->addOption('organization-description', null, InputOption::VALUE_OPTIONAL, 'Description')
            ->addOption('organization-enabled', null, InputOption::VALUE_OPTIONAL, '"Enabled" flag')
            ->setDescription('Updates an organization.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command updates an organization.

The <info>--organization-description</info> option can be used to update the organization description:

  <info>php %command.full_name% --organization-description=<description> <organization-name></info>

The <info>--organization-name</info> option can be used to rename an organization.
The provided value becomes the new name:

  <info>php %command.full_name% --organization-name=<new-name> <old-name></info>

The <info>--organization-enabled</info> option can be used to enable or disable an organization:

  <info>php %command.full_name% --organization-enabled=1 <organization-name></info>
  <info>php %command.full_name% --organization-enabled=0 <organization-name></info>

HELP
            )
            ->addUsage('--organization-description=<description> <organization-name>')
            ->addUsage('--organization-name=<new-name> <old-name>')
            ->addUsage('--organization-enabled=1 <organization-name>')
            ->addUsage('--organization-enabled=0 <organization-name>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $organizationName = $input->getArgument('organization-name');
            $organization = $this->organizationManager->getOrganizationByName($organizationName);

            if (!$organization) {
                throw new \InvalidArgumentException(sprintf('Organization "%s" not found.', $organizationName));
            }

            if (null !== $input->getOption('organization-name')) {
                $organization->setName((string)$input->getOption('organization-name'));
            }
            if (null !== $input->getOption('organization-description')) {
                $organization->setDescription((string)$input->getOption('organization-description'));
            }
            if (null !== $input->getOption('organization-enabled')) {
                $organization->setEnabled((bool)$input->getOption('organization-enabled'));
            }
            $this->organizationManager->updateOrganization($organization);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return $e->getCode() ?: 1;
        }

        return 0;
    }
}
