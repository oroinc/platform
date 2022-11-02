<?php
declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates MD5 hashes for email template contents.
 */
class GenerateMd5ForEmailsCommand extends Command
{
    protected static $defaultName = 'oro:email:generate-md5';

    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->setDescription('Generates MD5 hashes for email template contents.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command generates and prints MD5 hashes for email template contents
from <comment>oro_email_template</comment> table. These hashes can be used in email migrations.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $emailTemplates = $this->doctrineHelper->getEntityRepository(EmailTemplate::class)->findAll();

        /** @var EmailTemplate $template */
        foreach ($emailTemplates as $template) {
            $output->write($template->getName() . ':'. \md5($template->getContent()), true);
        }

        return 0;
    }
}
