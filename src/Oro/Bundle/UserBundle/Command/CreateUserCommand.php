<?php

namespace Oro\Bundle\UserBundle\Command;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Exception\InvalidArgumentException;

class CreateUserCommand extends ContainerAwareCommand
{
    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:user:create')
            ->setDescription('Create user.')
            ->addOption('user-role', null, InputOption::VALUE_REQUIRED, 'User role')
            ->addOption('user-business-unit', null, InputOption::VALUE_REQUIRED, 'User business unit')
            ->addOption('user-name', null, InputOption::VALUE_REQUIRED, 'User name')
            ->addOption('user-email', null, InputOption::VALUE_REQUIRED, 'User email')
            ->addOption('user-firstname', null, InputOption::VALUE_REQUIRED, 'User first name')
            ->addOption('user-lastname', null, InputOption::VALUE_REQUIRED, 'User last name')
            ->addOption('user-password', null, InputOption::VALUE_REQUIRED, 'User password')
            ->addOption(
                'user-organizations',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'User organizations'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var User $user */
        $user = $this->getUserManager()->createUser();
        $user->setEnabled(true);

        $options = $input->getOptions();

        try {
            $this->updateUser($user, $options);
        } catch (InvalidArgumentException $exception) {
            $output->writeln($exception->getMessage());
        } catch (DBALException $exception) {
            $output->writeln($exception->getMessage());
            $output->writeln($exception->getCode());
            $output->writeln($exception->getFile());
            $output->writeln($exception->getLine());
            $output->writeln('User exists');
        }
    }

    /**
     * @param User  $user
     * @param array $options
     * @throws InvalidArgumentException
     */
    protected function updateUser(User $user, $options)
    {
        if (!empty($options['user-role'])) {
            $role = $this->getEntityManager()
                ->getRepository('OroUserBundle:Role')
                ->findOneBy(array('role' => $options['user-role']));

            if (!$role) {
                throw new InvalidArgumentException('Invalid Role');
            }

            $user->addRole($role);
        }

        if (!empty($options['user-business-unit'])) {
            $businessUnit = $this->getEntityManager()
                ->getRepository('OroOrganizationBundle:BusinessUnit')
                ->findOneBy(array('name' => $options['user-business-unit']));

            if (!$businessUnit) {
                throw new InvalidArgumentException('Invalid Business Unit');
            }

            $user
                ->setOwner($businessUnit)
                ->setOrganization($businessUnit->getOrganization())
                ->addBusinessUnit($businessUnit);
        }

        if (!empty($options['user-name'])) {
            $user->setUsername($options['user-name']);
        }

        if (!empty($options['user-password'])) {
            $user->setPlainPassword($options['user-password']);
        }

        $properties = ['email', 'firstname', 'lastname'];
        foreach ($properties as $property) {
            if (!empty($options['user-' . $property])) {
                $user->{'set' . str_replace('-', '', $property)}($options['user-' . $property]);
            }
        }

        $this->setOrganizations($user, $options);

        $this->getUserManager()->updateUser($user);
    }

    /**
     * @param User $user
     * @param array $options
     * @throws InvalidArgumentException
     */
    protected function setOrganizations(User $user, $options)
    {
        if (empty($options['user-organizations'])) {
            return;
        }

        foreach ($options['user-organizations'] as $organizationName) {
            try {
                $organization = $this->getEntityManager()
                    ->getRepository('OroOrganizationBundle:Organization')
                    ->getOrganizationByName($organizationName);
            } catch (NoResultException $e) {
                throw new InvalidArgumentException('Invalid organization "' . $organizationName .
                    '" in "--user-organizations" parameter');
            }

            $user->addOrganization($organization);
        }
    }

    /**
     * @return UserManager
     */
    protected function getUserManager()
    {
        if (!$this->userManager) {
            $this->userManager = $this->getContainer()->get('oro_user.manager');
        }

        return $this->userManager;
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
