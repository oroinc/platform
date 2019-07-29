<?php

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates and prints md5 for email templates content from `oro_email_template` table. Hashes
 * can be used in email template migrations.
 */
class GenerateMd5ForEmailsCommand extends Command
{
    protected static $defaultName = 'oro:email:generate-md5';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
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
        $emailTemplates = $this->doctrineHelper->getEntityRepository(EmailTemplate::class)->findAll();

        /** @var EmailTemplate $template */
        foreach ($emailTemplates as $template) {
            $output->write($template->getName() . ':'. \md5($template->getContent()), true);
        }
    }
}
