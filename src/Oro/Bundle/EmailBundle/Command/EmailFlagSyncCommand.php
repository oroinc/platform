<?php

namespace Oro\Bundle\EmailBundle\Command;

use Exception;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EmailBundle\Manager\EmailFlagManager;
use Oro\Component\Log\OutputLogger;

class EmailFlagSyncCommand extends ContainerAwareCommand
{
    const SEEN = 'seen';
    const IDS = 'ids';

    /**
     * {@internaldoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:email:flag-sync')
            ->setDescription('Synchronization email flags')
            ->addOption(
                self::SEEN,
                null,
                InputOption::VALUE_REQUIRED,
                'The seen status true or false.'
            )
            ->addOption(
                self::IDS,
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'The identifiers of email user to be synchronized.'
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

        $seenStatus = $input->getOption('seen') === 'true';
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
                    $msg = sprintf('Email flag synced for email user - %s', $emailUser->getId());
                    $output->writeln('<info>' . $msg . '</info>');
                } catch (Exception $e) {
                    $warn = sprintf('Email flag cannot be synced for email user - %s', $emailUser->getId());
                    $output->writeln('<info>' . $warn . '</info>');
                    $logger->warning($warn);
                }

                $emailUser->decrementUnsyncedFlagCount();
            } else {
                $warn = sprintf('Not found email user - %s', $emailUser->getId());
                $output->writeln('<info>' . $warn . '</info>');
                $logger->warning($warn);
            }
        }

        $this->getEmailUserManager()->flush();
    }

    /**
     * @return ObjectManager
     */
    protected function getEmailUserManager()
    {
        return $this->getRegistry()->getManagerForClass('OroEmailBundle:EmailUser');
    }

    /**
     * @return Registry
     */
    protected function getRegistry()
    {
        return $this->getContainer()->get('doctrine');
    }
}
