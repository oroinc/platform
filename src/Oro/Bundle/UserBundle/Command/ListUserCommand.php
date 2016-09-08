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
            ->setDescription('Create user.')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Also list inactive users')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit the result set', 20)
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
        $offset = ($input->getOption('page') - 1) * $input->getOption('limit');

        $builder = $this
            ->getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository('OroUserBundle:User')
            ->createQueryBuilder('u')
            ->orderBy('u.id', 'ASC')
            ->setMaxResults($input->getOption('limit'))
            ->setFirstResult($offset);

        if (!$input->getOption('all')) {
            $builder
                ->andWhere('u.enabled = :enabled')
                ->setParameter('enabled', true);
        }

        if (!empty($input->getOption('roles'))) {
            $builder
                ->leftJoin('u.roles', 'r')
                ->andWhere('r.label IN (:roles)')
                ->setParameter('roles', $input->getOption('roles'));
        }

        $table = new Table($output);
        $table
            ->setHeaders(['ID', 'Username', 'Status', 'First Name', 'Last Name', 'Roles'])
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
            $user->isEnabled() ? 'Active' : 'Inactive',
            $user->getFirstName(),
            $user->getLastName(),
            implode(', ', $user->getRoles())
        ];
    }
}
