<?php
declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Yaml\Yaml;

/**
 * Renders an email template.
 * Optionally, sends a compiled email to the email address specified in the "recipient" option.
 */
class DebugEmailTemplateCompileCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'oro:debug:email:template:compile';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Renders an email template.'
    . ' Optionally, sends a compiled email to the email address specified in the "recipient" option.';

    private DoctrineHelper $doctrineHelper;

    private EmailRenderer $emailRenderer;

    private MailerInterface $mailer;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EmailRenderer $emailRenderer,
        MailerInterface $mailer
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->emailRenderer = $emailRenderer;
        $this->mailer = $mailer;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                'template',
                InputArgument::REQUIRED,
                'The name of email template to be compiled.'
            )
            ->addOption(
                'params-file',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to YML file with params for compilation.'
            )
            ->addOption(
                'entity-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'An entity ID.'
            )
            ->addOption(
                'recipient',
                null,
                InputOption::VALUE_OPTIONAL,
                'Recipient email address. [Default: null]',
                null
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $templateName = $input->getArgument('template');

        $template = $this->doctrineHelper
            ->getEntityRepositoryForClass(EmailTemplate::class)
            ->findOneBy(['name' => $templateName]);

        if (!$template) {
            $output->writeln(sprintf('Template "%s" not found', $templateName));

            return 1;
        }

        $params = $this->getNormalizedParams($input->getOption('params-file'));

        if ($template->getEntityName()) {
            $params['entity'] = $this->getEntity($template->getEntityName(), $input->getOption('entity-id'));
        }

        [$subject, $body] = $this->emailRenderer->compileMessage($template, $params);

        if (!$input->getOption('recipient')) {
            $output->writeln(sprintf('SUBJECT: %s', $subject));
            $output->writeln('');
            $output->writeln('BODY:');
            $output->writeln($body);
        } else {
            $emailMessage = (new Email())
                ->subject($subject);

            if ($template->getType() === 'html') {
                $emailMessage->html($body);
            } else {
                $emailMessage->text($body);
            }

            $emailMessage->from($input->getOption('recipient'));
            $emailMessage->to($input->getOption('recipient'));

            try {
                $this->mailer->send($emailMessage);
                $output->writeln(sprintf('Message was successfully sent to "%s"', $input->getOption('recipient')));
            } catch (\RuntimeException $e) {
                $output->writeln(sprintf('Message was not sent due to error: "%s"', $e->getMessage()));
            }
        }

        return 0;
    }

    private function getNormalizedParams(?string $paramsFile = null): array
    {
        if ($paramsFile && is_file($paramsFile) && is_readable($paramsFile)) {
            return Yaml::parse(file_get_contents($paramsFile));
        }

        return [];
    }

    /**
     * @param string $entityClass
     * @param null|mixed $entityId
     *
     * @return object
     */
    private function getEntity(string $entityClass, $entityId = null)
    {
        $entity = $this->doctrineHelper->createEntityInstance($entityClass);

        if ($entityId) {
            $entity = $this->doctrineHelper->getEntity($entityClass, $entityId);

            if (!$entity) {
                throw new \RuntimeException(sprintf('Entity "%s" with id "%s" not found', $entityClass, $entityId));
            }
        }

        return $entity;
    }
}
