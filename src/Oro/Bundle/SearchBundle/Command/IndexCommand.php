<?php

namespace Oro\Bundle\SearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
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
    protected function configure()
    {
        $this->setName('oro:search:index')
             ->setDescription('Internal command (do not use). Process search index queue.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting index task');

        $doctrine       = $this->getContainer()->get('doctrine');
        $engine         = $this->getContainer()->get('oro_search.search.engine');
        $entityManager  = $doctrine->getManager();
        $itemRepository = $entityManager->getRepository('OroSearchBundle:Item');

        if ($this->getContainer()->hasParameter('oro_search.drivers')) {
            $itemRepository->setDriversClasses($this->getContainer()->getParameter('oro_search.drivers'));
        }

        $changed = $itemRepository->findBy(array('changed' => true));

        // TODO: probably, fulltext index should be dropped here for performance reasons

        foreach ($changed as $item) {
            $output->write(sprintf('  Processing "%s" with id #%u', $item->getEntity(), $item->getRecordId()));

            $entity = $doctrine
                ->getRepository($item->getEntity())
                ->find($item->getRecordId());

            if ($entity) {
                $item->setChanged(false)
                    ->setTitle($engine->getEntityTitle($entity))
                    ->saveItemData($engine->getMapper()->mapObject($entity));
            } else {
                $entityManager->remove($item);
            }
        }

        $entityManager->flush();

        // recreate fulltext index, if necessary
        // ...

        $output->writeln(sprintf('Total indexed items: %u', count($changed)));
    }
}
