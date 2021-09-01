<?php
declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Command;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Exception\InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creates a user.
 */
class CreateUserCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:user:create';

    protected UserManager $userManager;
    protected EntityManagerInterface $entityManager;

    public function __construct(UserManager $userManager, EntityManagerInterface $entityManager)
    {
        $this->userManager = $userManager;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption('user-role', null, InputOption::VALUE_REQUIRED, 'Role')
            ->addOption('user-business-unit', null, InputOption::VALUE_REQUIRED, 'Business unit')
            ->addOption('user-name', null, InputOption::VALUE_REQUIRED, 'Username')
            ->addOption('user-email', null, InputOption::VALUE_REQUIRED, 'Email')
            ->addOption('user-firstname', null, InputOption::VALUE_REQUIRED, 'First name')
            ->addOption('user-lastname', null, InputOption::VALUE_REQUIRED, 'Last name')
            ->addOption('user-password', null, InputOption::VALUE_REQUIRED, 'Password')
            ->addOption(
                'user-organizations',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Organizations'
            )
            ->setDescription('Creates a user.')
            // @codingStandardsIgnoreStart
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command creates a user.

  <info>php %command.full_name% --user-name=<username> --user-email=<email> --user-password=<password> --user-business-unit=<business-unit-id></info>

The <info>--user-name</info>, <info>--user-email</info>, <info>--user-password</info> and <info>--user-business-unit</info> options are required and should be used to specify the username, email and password for the new user and the business unit in which the user should be created:

  <info>php %command.full_name% --user-name=<username> --user-email=<email> --user-password=<password> --user-business-unit=<business-unit-id></info>

The <info>--user-firstname</info>, <info>--user-lastname</info> and <info>--user-role</info> options can be used to provide additional details:

  <info>php %command.full_name% --user-name=<username> --user-email=<email> --user-password=<password> --user-business-unit=<business-unit-id> --user-firstname=<firstname> --user-lastname=<lastname> --user-role=<role></info>

HELP
            )
            ->addUsage('--user-name=<username> --user-email=<email> --user-password=<password> --user-business-unit=<business-unit-id>')
            ->addUsage('--user-name=<username> --user-email=<email> --user-password=<password> --user-business-unit=<business-unit-id> --user-firstname=<firstname> --user-lastname=<lastname> --user-role=<role>')
            // @codingStandardsIgnoreEnd
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var User $user */
        $user = $this->userManager->createUser();
        $user->setEnabled(true);

        $options = $input->getOptions();

        try {
            $this
                ->checkRequiredOptions($options)
                ->updateUser($user, $options);
        } catch (InvalidArgumentException $exception) {
            $output->writeln($exception->getMessage());

            return $exception->getCode() ?: 1;
        } catch (DBALException $exception) {
            $output->writeln('User exists');

            return $exception->getCode() ?: 1;
        }

        return 0;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function checkRequiredOptions(array $options): self
    {
        $requiredOptions = [
            'user-business-unit',
            'user-name',
            'user-email',
            'user-password'
        ];

        foreach ($requiredOptions as $requiredOption) {
            if (empty($options[$requiredOption])) {
                throw new InvalidArgumentException('--' . $requiredOption . ' option required');
            }
        }

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function updateUser(User $user, array $options): void
    {
        if (!empty($options['user-name'])) {
            $user->setUsername($options['user-name']);
        }

        if (!empty($options['user-password'])) {
            $user->setPlainPassword($options['user-password']);
        }

        $this
            ->setRole($user, $options)
            ->setBusinessUnit($user, $options)
            ->setOrganizations($user, $options)
            ->setProperties($user, $options);

        $this->userManager->updateUser($user);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function setRole(User $user, array $options): self
    {
        $roleName = null;
        if (!empty($options['user-role'])) {
            $roleName = $options['user-role'];
        } elseif (null === $user->getId()) {
            $roleName = User::ROLE_DEFAULT;
        }
        if ($roleName) {
            $role = $this->entityManager
                ->getRepository(Role::class)
                ->findOneBy(['role' => $roleName]);

            if (!$role) {
                throw new InvalidArgumentException('Invalid Role');
            }

            $user->addUserRole($role);
        }

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function setBusinessUnit(User $user, array $options): self
    {
        if (!empty($options['user-business-unit'])) {
            $businessUnit = $this->entityManager
                ->getRepository(BusinessUnit::class)
                ->findOneBy(['name' => $options['user-business-unit']]);

            if (!$businessUnit) {
                throw new InvalidArgumentException('Invalid Business Unit');
            }

            $user
                ->setOwner($businessUnit)
                ->setOrganization($businessUnit->getOrganization())
                ->addOrganization($businessUnit->getOrganization())
                ->addBusinessUnit($businessUnit);
        }

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function setOrganizations(User $user, array $options): self
    {
        if (!empty($options['user-organizations'])) {
            foreach ($options['user-organizations'] as $organizationName) {
                try {
                    $organization = $this->entityManager
                        ->getRepository(Organization::class)
                        ->getOrganizationByName($organizationName);
                } catch (NoResultException $e) {
                    throw new InvalidArgumentException('Invalid organization "' . $organizationName .
                        '" in "--user-organizations" parameter');
                }

                $user->addOrganization($organization);
            }
        }

        return $this;
    }

    protected function setProperties(User $user, array $options): self
    {
        $properties = ['email', 'firstname', 'lastname'];
        foreach ($properties as $property) {
            if (!empty($options['user-' . $property])) {
                $user->{'set' . str_replace('-', '', $property)}($options['user-' . $property]);
            }
        }

        return $this;
    }
}
