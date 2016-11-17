<?php

namespace Oro\Bundle\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\UserBundle\Entity\User;

class ListUserCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:user:list')
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

        $builder = $this
            ->getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository('OroUserBundle:User')
            ->createQueryBuilder('u')
            ->orderBy('u.enabled', 'DESC')
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
