<?php

namespace Oro\Bundle\SearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\ORM\EntityManager;

class IndexCommand extends ContainerAwareCommand
{
    const NAME = 'oro:search:index';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Update search index for specified entities with the same type')
            ->addArgument(
                'class',
                InputArgument::REQUIRED,
                'Full or compact class name of indexed entities ' .
                '(f.e. Oro\Bundle\UserBundle\Entity\User or OroUserBundle:User)'
            )
            ->addArgument(
                'identifiers',
                InputArgument::REQUIRED|InputArgument::IS_ARRAY,
                'Identifiers of indexed entities (f.e. 42)'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $engine      = $this->getContainer()->get('oro_search.search.engine');
        $class       = $input->getArgument('class');
        $identifiers = $input->getArgument('identifiers');

        // convert from short format to FQСN
        $class = $this->getContainer()->get('doctrine')
            ->getManagerForClass($class)->getClassMetadata($class)->getName();

        list($savedEntities, $deletedEntities) = $this->getSavedAndDeletedEntities($class, $identifiers);

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

    /**
     * @param string $class
     * @param array $identifiers
     * @return array
     * @throws \LogicException
     */
    protected function getSavedAndDeletedEntities($class, array $identifiers)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine')->getManagerForClass($class);
        if (!$entityManager) {
            throw new \LogicException(sprintf('Entity manager for class %s is not defined', $class));
        }

        $repository = $entityManager->getRepository($class);
        $metadata = $entityManager->getClassMetadata($class);

        $identifierColumn = $metadata->getSingleIdentifierFieldName();
        $queryBuilder = $repository->createQueryBuilder('e');

        // get entities to save
        $savedEntityIds = array();
        $savedEntities = $queryBuilder->andWhere($queryBuilder->expr()->in('e.' . $identifierColumn, $identifiers))
            ->getQuery()
            ->getResult();

        foreach ($savedEntities as $entity) {
            $ids = $metadata->getIdentifierValues($entity);
            $savedEntityIds[] = current($ids);
        }

        // get entities to delete
        $deletedEntityIds = array_diff($identifiers, $savedEntityIds);
        $deletedEntities = array();

        foreach ($deletedEntityIds as $id) {
            $deletedEntities[] = $entityManager->getReference($class, $id);
        }

        return array($savedEntities, $deletedEntities);
    }
}
