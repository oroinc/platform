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
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Exports email templates
 */
class EmailTemplatesExportCommand extends Command
{
    protected static $defaultName = 'oro:email:template:export';

    protected static $defaultDescription = 'Exports email templates';

    private DoctrineHelper $doctrineHelper;

    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        parent::__construct();

        $this->doctrineHelper = $doctrineHelper;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function setPropertyAccessor(?PropertyAccessorInterface $propertyAccessor): void
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    #[\Override]
    protected function configure()
    {
        $this
            ->addArgument('destination', InputArgument::REQUIRED, "Folder to export")
            ->addOption('template', null, InputOption::VALUE_OPTIONAL, "template name");
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $destination = $input->getArgument('destination');

        if (!is_dir($destination) || !is_writable($destination)) {
            $output->writeln(sprintf('<error>Destination path "%s" should be writable folder</error>', $destination));

            return Command::FAILURE;
        }

        $templates = $this->getEmailTemplates($input->getOption('template'));
        $output->writeln(sprintf('Found %d templates for export', count($templates)));

        foreach ($templates as $template) {
            $templateMetadata = [];
            foreach (['name', 'entityName', 'subject', 'attachments', 'isSystem', 'isEditable'] as $property) {
                $value = $this->propertyAccessor->getValue($template, $property);
                if (is_iterable($value)) {
                    if ($value instanceof \Traversable) {
                        $value = iterator_to_array($value);
                    }
                    $value = json_encode(array_map(static fn ($each) => (string)$each, $value), JSON_THROW_ON_ERROR);
                }
                $templateMetadata[] = '@' . $property . ' = ' . $value;
            }

            $content = sprintf(
                "%s\n\n%s",
                implode("\n", $templateMetadata),
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

        return Command::SUCCESS;
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
