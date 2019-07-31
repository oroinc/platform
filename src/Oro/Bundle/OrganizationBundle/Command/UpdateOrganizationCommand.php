<?php

namespace Oro\Bundle\OrganizationBundle\Command;

use Oro\Bundle\OrganizationBundle\Entity\Manager\OrganizationManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Update organization by name
 */
class UpdateOrganizationCommand extends Command
{
    protected static $defaultName = 'oro:organization:update';

    /** @var OrganizationManager */
    private $organizationManager;

    /**
     * @param OrganizationManager $organizationManager
     */
    public function __construct(OrganizationManager $organizationManager)
    {
        $this->organizationManager = $organizationManager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Update organization by name.')
            ->addArgument('organization-name', InputArgument::REQUIRED, 'Organization name to update')
            ->addOption('organization-name', null, InputOption::VALUE_OPTIONAL, 'Organization name')
            ->addOption('organization-description', null, InputOption::VALUE_OPTIONAL, 'Organization description')
            ->addOption('organization-enabled', null, InputOption::VALUE_OPTIONAL, 'Organization enabled');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $organizationName = $input->getArgument('organization-name');
        $organization     = $this->organizationManager->getOrganizationByName($organizationName);
        $options          = $input->getOptions();

        if (!$organization) {
            throw new \InvalidArgumentException(sprintf('Organization "%s" not found.', $organizationName));
        }

        try {
            $this->updateOrganization($organization, $options);
        } catch (\InvalidArgumentException $exception) {
            $output->writeln($exception->getMessage());
        }
    }

    /**
     * @param Organization $organization
     * @param array        $options
     */
    protected function updateOrganization(Organization $organization, $options)
    {
        $properties = ['name', 'description', 'enabled'];
        foreach ($properties as $property) {
            if (!empty($options['organization-' . $property])) {
                $organization->{'set' . ucfirst($property)}($options['organization-' . $property]);
            }
        }

        $this->organizationManager->updateOrganization($organization);
    }
}
