<?php

namespace Oro\Bundle\UserBundle\Command;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;

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
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'User role')
            ->addOption('business-unit', null, InputOption::VALUE_REQUIRED, 'User business unit')
            ->addOption('username', null, InputOption::VALUE_REQUIRED, 'User name')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'User email')
            ->addOption('firstname', null, InputOption::VALUE_REQUIRED, 'User first name')
            ->addOption('lastname', null, InputOption::VALUE_REQUIRED, 'User last name')
            ->addOption('plain-password', null, InputOption::VALUE_REQUIRED, 'User password');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = $this->getUserManager()->createUser();
        $user->setEnabled(true);

        $options = $input->getOptions();

        try {
            $this->updateUser($user, $options);
        } catch (InvalidArgumentException $exception) {
            $output->writeln($exception->getMessage());
        } catch (DBALException $exception) {
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
        if (!empty($options['role'])) {
            $role = $this->getEntityManager()
                ->getRepository('OroUserBundle:Role')
                ->findOneBy(array('role' => $options['role']));

            if (!$role) {
                throw new InvalidArgumentException('Invalid Role');
            }

            $user->addRole($role);
        }

        if (!empty($options['business-unit'])) {
            $businessUnit = $this->getEntityManager()
                ->getRepository('OroOrganizationBundle:BusinessUnit')
                ->findOneBy(array('name' => $options['business-unit']));

            if (!$businessUnit) {
                throw new InvalidArgumentException('Invalid Business Unit');
            }

            $user
                ->setOwner($businessUnit)
                ->addBusinessUnit($businessUnit);
        }

        $properties = ['name', 'email', 'firstname', 'lastname', 'plain-password'];

        foreach ($properties as $property) {
            if (!empty($options[$property])) {
                $user->{'set' . str_replace('-', '', $property)}($options[$property]);
            }
        }

        $this->getUserManager()->updateUser($user);
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
