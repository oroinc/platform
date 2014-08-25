<?php

namespace Oro\Bundle\SearchBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Update and reindex (automatically) fulltext-indexed table(s).
 * Use carefully on large data sets - do not run this task too often.
 *
 * @author magedan
 */
class IndexCommand extends ContainerAwareCommand
{
    const NAME = 'oro:search:index';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Do search index for one specific entity')
            ->addArgument(
                'class',
                InputArgument::REQUIRED,
                'Full or compact class name of indexed entity ' .
                '(f.e. Oro\Bundle\UserBundle\Entity\User or OroUserBundle:User)'
            )
            ->addArgument(
                'identifier',
                InputArgument::REQUIRED|InputArgument::IS_ARRAY,
                'Identifiers of indexed entities (f.e. 42)'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine   = $this->getContainer()->get('doctrine');
        $engine     = $this->getContainer()->get('oro_search.search.engine');
        $class      = $input->getArgument('class');
        $identifier = $input->getArgument('identifier');

        /** @var EntityManager $entityManager */
        $entityManager = $doctrine->getManagerForClass($class);
        if (!$entityManager) {
            throw new \LogicException(sprintf('Entity manager for class %s is not defined', $class));
        }

        $savedEntities   = array();
        $deletedEntities = array();
        foreach ($identifier as $id) {
            $entity = $entityManager->find($class, $id);
            if ($entity) {
                $savedEntities[] = $entity;
            } else {
                $deletedEntities[] = $entityManager->getReference($class, $id);
            }
        }

        if ($savedEntities) {
            if ($engine->save($savedEntities, true)) {
                $output->writeln('<info>Entities successfully updated in index</info>');
            } else {
                $output->writeln('<error>Can\'t update entities in index</error>');
            }
        }

        if ($deletedEntities) {
            if ($engine->delete($deletedEntities, true)) {
                $output->writeln('<info>Entities successfully deleted from index</info>');
            } else {
                $output->writeln('<error>Can\'t delete entities from index</error>');
            }
        }
    }
}
