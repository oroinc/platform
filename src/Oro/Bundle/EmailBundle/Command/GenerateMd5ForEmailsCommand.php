<?php

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates and prints md5 for email templates content from `oro_email_template` table. Hashes
 * can be used in email template migrations.
 */
class GenerateMd5ForEmailsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:email:generate-md5')
            ->setDescription(
                'Generates and prints md5 for email templates content from `oro_email_template` table. Hashes 
                can be used in email migrations'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getContainer()->get('oro_entity.doctrine_helper');

        $emailTemplates = $helper->getEntityRepository(EmailTemplate::class)->findAll();

        /** @var EmailTemplate $template */
        foreach ($emailTemplates as $template) {
            $output->write($template->getName() . ':'. \md5($template->getContent()), true);
        }
    }
}
