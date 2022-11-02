<?php
declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Exports email templates
 */
class EmailTemplatesExportCommand extends Command
{
    protected static $defaultName = 'oro:email:template:export';

    protected static $defaultDescription = 'Exports email templates';

    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        parent::__construct();

        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addArgument('destination', InputArgument::REQUIRED, "Folder to export")
            ->addOption('template', null, InputOption::VALUE_OPTIONAL, "template name");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $destination = $input->getArgument('destination');

        if (!is_dir($destination) || !is_writable($destination)) {
            $output->writeln(sprintf('<error>Destination path "%s" should be writable folder</error>', $destination));

            return 1;
        }

        $templates = $this->getEmailTemplates($input->getOption('template'));
        $output->writeln(sprintf('Found %d templates for export', count($templates)));

        /** @var EmailTemplate $template */
        foreach ($templates as $template) {
            $content = sprintf(
                "@name = %s\n@entityName = %s\n@subject = %s\n@isSystem = %d\n@isEditable = %d\n\n%s",
                $template->getName(),
                $template->getEntityName(),
                $template->getSubject(),
                $template->getIsSystem(),
                $template->getIsEditable(),
                $template->getContent()
            );

            $filename = sprintf(
                "%s.%s.twig",
                preg_replace('/[^a-z0-9._-]+/i', '', $template->getName()),
                $template->getType() ?: 'html'
            );

            file_put_contents(
                $destination . DIRECTORY_SEPARATOR . $filename,
                $content
            );
        }

        return 0;
    }

    /**
     * @param null $templateName
     *
     * @return EmailTemplate[]
     * @throws \UnexpectedValueException
     */
    private function getEmailTemplates($templateName = null)
    {
        $criterion = [];
        if ($templateName) {
            $criterion = ['name' => $templateName];
        }

        return $this->doctrineHelper
            ->getEntityRepositoryForClass(EmailTemplate::class)
            ->findBy($criterion);
    }
}
