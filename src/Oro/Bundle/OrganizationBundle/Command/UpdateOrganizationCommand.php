<?php

namespace Oro\Bundle\OrganizationBundle\Command;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Manager\OrganizationManager;

class UpdateOrganizationCommand extends ContainerAwareCommand
{
    /** @var OrganizationManager */
    protected $organizationManager;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:organization:update')
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
        $organization     = $this->getOrganizationManager()->getOrganizationByName($organizationName);
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

        $this->getOrganizationManager()->updateOrganization($organization);
    }

    /**
     * @return OrganizationManager
     */
    protected function getOrganizationManager()
    {
        if (!$this->organizationManager) {
            $this->organizationManager = $this->getContainer()->get('oro_organization.organization_manager');
        }

        return $this->organizationManager;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        if (!$this->entityManager) {
            $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        }

        return $this->entityManager;
    }
}
