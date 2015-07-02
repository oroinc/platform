<?php

namespace Oro\Bundle\EmailBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EmailBundle\Manager\EmailFlagManager;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
use Oro\Component\Log\OutputLogger;

class EmailFlagSyncCommand extends ContainerAwareCommand
{
    /**
     * {@internaldoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:email:flag-sync')
            ->setDescription('Synchronization email flags')
            ->addOption(
                'seen',
                null,
                InputOption::VALUE_REQUIRED,
                'The seen status 1 or 0.'
            )
            ->addOption(
                'ids',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'The identifier of email user to be synchronized.'
            );
    }

    /**
     * {@internaldoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new OutputLogger($output);

        /** @var EmailFlagManager $emailFlagManager */
        $emailFlagManager = $this->getContainer()->get('oro_email.email.flag.manager');

        $seenStatus = $input->getOption('seen');
        $emailUserIds = $input->getOption('ids');
        foreach ($emailUserIds as $emailUserId) {
            $emailUser = $this->getContainer()->get('doctrine')
                ->getRepository('OroEmailBundle:EmailUser')->find($emailUserId);
            if ($emailUser) {
                try {
                    if ($seenStatus) {
                        $emailFlagManager->setSeen($emailUser);
                    } else {
                        $emailFlagManager->setUnseen($emailUser);
                    }
                    $output->writeln(
                        sprintf('<info>Email flag synced for email user - %s</info>', $emailUser->getId())
                    );
                } catch (LoadEmailBodyException $e) {
                    $warn = sprintf('Email flag cannot be synced for email user - %s', $emailUser->getId());
                    $output->writeln('<info>' . $warn . '</info>');
                    $logger->warning($warn);
                }
            }
        }
    }
}
