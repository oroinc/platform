<?php

namespace Oro\Bundle\ImapBundle\Command;

use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Component\Log\OutputLogger;
use Oro\Bundle\ImapBundle\Entity\ImapEmailOrigin;

class ClearInactiveMailboxCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var OutputLogger
     */
    protected $logger;
    /**
     * {@internaldoc}
     */
    protected function configure()
    {
        $this->setName('oro:imap:clear-mailbox')
            ->setDescription('Clears inactive mailboxes')
            ->addOption(
                'id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Id of origin to clear'
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger = new OutputLogger($output);

        $this->em = $this->getContainer()->get('doctrine')->getManager();

        $originId = $input->getOption('id');
        $origins = $this->getOriginsToClear($originId);
        if (!$origins) {
            $this->logger->notice('Nothing to clear');

            return;
        }

        foreach ($origins as $origin) {
            $this->logger->notice(sprintf('Clearing origin: %s, %s', $origin->getId(), $origin));

            $this->clearOrigin($origin);

            $this->logger->notice('Origin processed successfully');
        }

        $this->logger->notice('Finished');
    }

    /**
     * @param int $originId
     *
     * @return ImapEmailOrigin[]
     * @throws \Exception
     */
    protected function getOriginsToClear($originId)
    {
        $originRepository = $this->em->getRepository('OroImapBundle:ImapEmailOrigin');

        if ($originId !== null) {
            /** @var ImapEmailOrigin $origin */
            $origin = $originRepository->find($originId);
            if ($origin === null) {
                $this->logger->notice(sprintf('Origin with ID %s does not exist', $originId));

                return [];
            }

            $origins = [$origin];
        } else {
            $origins = $originRepository->findAll();
        }

        return $origins;
    }

    /**
     * @param ImapEmailOrigin $origin
     */
    protected function clearOrigin(ImapEmailOrigin $origin)
    {
        $folders = $origin->getFolders();
        $folderRepository = $this->em->getRepository('OroImapBundle:ImapEmailFolder');

        foreach ($folders as $folder) {
            $imapFolder = $this->em->getRepository('OroImapBundle:ImapEmailFolder')
                ->findOneBy(['folder' => $folder]);

            if (!$origin->isActive()) {
                $folderRepository->removeFolder($imapFolder);
            } elseif (!$folder->isSyncEnabled()) {
                $folderRepository->clearFolder($imapFolder);
            }
        }

        if (!$origin->isActive()) {
            $this->em->remove($origin);
            $this->em->flush();
        }
    }
}
