<?php

namespace Oro\Bundle\EmailBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
use Oro\Component\Log\OutputLogger;

/**
 * @deprecated Cron command oro:cron:email-body-sync should be used instead
 */
class EmailBodySyncCommand extends ContainerAwareCommand
{
    /**
     * {@internaldoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:email:body-sync')
            ->setDescription(
                'Synchronization email body. This command is deprecated. '
                .'Cron command oro:cron:email-body-sync should be used instead'
            )
            ->addOption(
                'id',
                null,
                InputOption::VALUE_REQUIRED,
                'The identifier of email to be synchronized.'
            );
    }

    /**
     * {@internaldoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new OutputLogger($output);

        /** @var EmailCacheManager $emailCacheManager */
        $emailCacheManager = $this->getContainer()->get('oro_email.email.cache.manager');

        $emailId = $input->getOption('id');
        $email = $this->getContainer()->get("doctrine")->getRepository('OroEmailBundle:Email')->find($emailId);
        if ($email) {
            try {
                $emailCacheManager->ensureEmailBodyCached($email);
                $output->writeln(sprintf('<info>Email body synced for email - %s</info>', $email->getId()));
            } catch (LoadEmailBodyException $e) {
                $warn = sprintf('Email body cannot be loaded for email - %s', $email->getId());
                $output->writeln('<info>' . $warn . '</info>');
                $logger->warning($warn);
            }
        }
    }
}
