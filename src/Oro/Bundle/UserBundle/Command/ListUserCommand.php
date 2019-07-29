<?php

namespace Oro\Bundle\UserBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists users. By default shows a paginated list of the active (enabled) users.
 */
class ListUserCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:user:list';

    /** @var ManagerRegistry */
    private $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription("Lists users.\nBy default shows a paginated list of the active (enabled) users.")
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Also list inactive users')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limits the number of results (-1 for all)', 20)
            ->addOption('page', 'p', InputOption::VALUE_REQUIRED, 'Page of the result set', 1)
            ->addOption(
                'roles',
                'r',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Filter by roles (ANY)',
                []
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $limit = (int) $input->getOption('limit');
        $offset = ((int) $input->getOption('page') - 1) * $limit;

        /** @var QueryBuilder $builder */
        $builder = $this->doctrine
            ->getManagerForClass(User::class)
            ->getRepository('OroUserBundle:User')
            ->createQueryBuilder('u');
        $builder->orderBy('u.enabled', 'DESC')
            ->addOrderBy('u.id', 'ASC');

        if ($offset > 0) {
            $builder->setFirstResult($offset);
        }

        if ($limit > 0) {
            $builder->setMaxResults($limit);
        }

        if (!$input->getOption('all')) {
            $builder
                ->andWhere('u.enabled = :enabled')
                ->setParameter('enabled', true);
        }

        if (!empty($input->getOption('roles'))) {
            $builder
                ->leftJoin('u.roles', 'r')
                ->leftJoin('u.auth_status', 'au')
                ->andWhere('r.label IN (:roles)')
                ->setParameter('roles', $input->getOption('roles'));
        }

        $table = new Table($output);
        $table
            ->setHeaders(['ID', 'Username', 'Enabled (Auth Status)', 'First Name', 'Last Name', 'Roles'])
            ->setRows(array_map([$this, 'getUserRow'], $builder->getQuery()->getResult()))
            ->render()
        ;
    }

    /**
     * @param  User   $user
     * @return array
     */
    protected function getUserRow(User $user)
    {
        return [
            $user->getId(),
            $user->getUsername(),
            sprintf(
                '%s (%s)',
                $user->isEnabled() ? 'Enabled' : 'Disabled',
                $user->getAuthStatus() ? $user->getAuthStatus()->getName() : null
            ),
            $user->getFirstName(),
            $user->getLastName(),
            implode(', ', $user->getRoles())
        ];
    }
}
