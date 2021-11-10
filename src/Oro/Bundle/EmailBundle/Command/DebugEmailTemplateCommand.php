<?php
declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Displays a list of current email templates for an application or an exact template
 */
class DebugEmailTemplateCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'oro:debug:email:template';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Displays a list of current email templates '
    . 'for an application or an exact template';

    private DoctrineHelper $doctrineHelper;

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
            ->addArgument(
                'template',
                InputArgument::OPTIONAL,
                'The name of email template to be debugged.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('template')) {
            return $this->processList($output);
        }

        return $this->processTemplate($input->getArgument('template'), $output);
    }

    /**
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function processList(OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders([
            'ID',
            'NAME',
            'ENTITY CLASS',
            'TYPE',
            'SYSTEM',
            'VISIBLE',
            'EDITABLE',
            'PARENT',
        ]);

        $templates = $this->doctrineHelper->getEntityRepositoryForClass(EmailTemplate::class)->findAll();

        /** @var EmailTemplate $template */
        foreach ($templates as $template) {
            $table->addRow([
                $template->getId(),
                $template->getName(),
                $template->getEntityName(),
                $template->getType(),
                $this->processBool($template->getIsSystem()),
                $this->processBool($template->isVisible()),
                $this->processBool($template->getIsEditable()),
                $template->getParent() ?: 'N/A',
            ]);
        }

        $table->render();

        return 0;
    }

    /**
     * @param string $templateName
     * @param OutputInterface $output
     *
     * @return int
     */
    private function processTemplate(string $templateName, OutputInterface $output)
    {
        $template = $this->doctrineHelper
            ->getEntityRepositoryForClass(EmailTemplate::class)
            ->findOneBy(['name' => $templateName]);

        if (!$template) {
            $output->writeln(sprintf('Template "%s" not found', $templateName));

            return 1;
        }

        $output->writeln(sprintf('@name = %s', $template->getName()));
        if ($template->getEntityName()) {
            $output->writeln(sprintf('@entityName = %s', $template->getEntityName()));
        }
        $output->writeln(sprintf('@subject = %s', $template->getSubject()));
        $output->writeln(sprintf('@isSystem = %s', $template->getIsSystem() ? 1 : 0));
        $output->writeln(sprintf('@isEditable = %s', $template->getIsEditable() ? 1 : 0));
        $output->writeln('');
        $output->writeln($template->getContent());

        return 0;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    private function processBool($value)
    {
        return $value ? 'Yes' : 'No';
    }
}
