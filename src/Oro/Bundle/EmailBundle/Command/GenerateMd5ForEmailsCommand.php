<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates MD5 hashes for email template contents.
 */
#[AsCommand(
    name: 'oro:email:generate-md5',
    description: 'Generates MD5 hashes for email template contents.'
)]
class GenerateMd5ForEmailsCommand extends Command
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
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
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $emailTemplates = $this->doctrineHelper->getEntityRepository(EmailTemplate::class)->findAll();

        /** @var EmailTemplate $template */
        foreach ($emailTemplates as $template) {
            $content = $template->getContent();
            /** @var EmailTemplateTranslation $templateTranslation */
            foreach ($template->getTranslations()->getValues() as $templateTranslation) {
                if (!$templateTranslation->isContentFallback()) {
                    $content .= $templateTranslation->getContent();
                }
            }
            $output->write($template->getName() . ':'. \md5($content), true);
        }

        return Command::SUCCESS;
    }
}
