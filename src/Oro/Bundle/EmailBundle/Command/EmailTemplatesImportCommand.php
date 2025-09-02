<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\EmailTemplateHydrator\EmailTemplateFromArrayHydrator;
use Oro\Bundle\EmailBundle\EmailTemplateHydrator\EmailTemplateRawDataParser;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Imports email templates from the directory
 */
class EmailTemplatesImportCommand extends Command
{
    protected static $defaultName = 'oro:email:template:import';

    protected static $defaultDescription = 'Imports email templates from the directory';

    private DoctrineHelper $doctrineHelper;

    private ?EmailTemplateRawDataParser $emailTemplateRawDataParser = null;

    private ?EmailTemplateFromArrayHydrator $emailTemplateFromRawDataHydrator = null;

    private ?Organization $organization = null;

    private ?User $adminUser = null;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        parent::__construct();

        $this->doctrineHelper = $doctrineHelper;
    }

    public function setEmailTemplateRawDataParser(?EmailTemplateRawDataParser $emailTemplateRawDataParser): void
    {
        $this->emailTemplateRawDataParser = $emailTemplateRawDataParser;
    }

    public function setEmailTemplateFromRawDataHydrator(
        ?EmailTemplateFromArrayHydrator $emailTemplateFromRawDataHydrator
    ): void {
        $this->emailTemplateFromRawDataHydrator = $emailTemplateFromRawDataHydrator;
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('source', InputArgument::REQUIRED, 'Folder or file to import')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force update');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = $input->getArgument('source');

        if (!$this->isValidSource($source)) {
            $output->writeln(sprintf('<error>Source path "%s" should exist and be readable</error>', $source));
            return Command::FAILURE;
        }

        $templates = $this->getRawTemplates($source);
        $output->writeln(sprintf('Found %d templates', count($templates)));

        // BC layer.
        if (!$this->emailTemplateRawDataParser || !$this->emailTemplateFromRawDataHydrator) {
            $this->bcProcessTemplates($templates, $input, $output);
        } else {
            $this->processTemplates($templates, $input->getOption('force'), $output);
        }

        $this->doctrineHelper->getEntityManagerForClass(EmailTemplate::class)->flush();

        return Command::SUCCESS;
    }

    private function isValidSource(string $source): bool
    {
        return (is_dir($source) || is_file($source)) && is_readable($source);
    }

    /**
     * @param array<string,array{path: string, format: string}> $templates
     * @param bool $forceUpdate
     * @param OutputInterface $output
     */
    private function processTemplates(array $templates, bool $forceUpdate, OutputInterface $output): void
    {
        foreach ($templates as $fileName => $file) {
            $arrayData = $this->prepareTemplateData($file, $fileName);

            if (!$this->validateTemplateData($arrayData, $fileName, $file['path'], $output)) {
                continue;
            }

            $emailTemplate = $this->findExistingTemplate($arrayData['name']) ?? new EmailTemplate();

            if ($emailTemplate->getId()) {
                $this->processExistingTemplate($emailTemplate, $arrayData, $forceUpdate, $output);
            } else {
                $this->processNewTemplate($emailTemplate, $arrayData, $output);
            }
        }
    }

    /**
     * @param array{path: string, format: string} $file
     * @param string $fileName
     *
     * @return array<string,mixed>
     */
    private function prepareTemplateData(array $file, string $fileName): array
    {
        $rawData = file_get_contents($file['path']);
        $arrayData = $this->emailTemplateRawDataParser->parseRawData($rawData);

        if (empty($arrayData['name'])) {
            $arrayData['name'] = $fileName;
        }

        if (empty($arrayData['type'])) {
            $arrayData['type'] = $file['format'];
        }

        return $arrayData;
    }

    private function validateTemplateData(
        array $arrayData,
        string $fileName,
        string $filePath,
        OutputInterface $output
    ): bool {
        if (empty($arrayData['name'])) {
            $output->writeln(
                sprintf('Skipping "%s": email template name is expected to be non empty', $filePath)
            );
            return false;
        }

        if ($arrayData['name'] !== $fileName) {
            $output->writeln(
                sprintf('Skipping "%s": email template name is expected to be equal to its filename', $filePath)
            );
            return false;
        }

        return true;
    }

    /**
     * @param EmailTemplate $emailTemplate
     * @param array<string,mixed> $arrayData
     * @param bool $forceUpdate
     * @param OutputInterface $output
     */
    private function processExistingTemplate(
        EmailTemplate $emailTemplate,
        array $arrayData,
        bool $forceUpdate,
        OutputInterface $output
    ): void {
        if ($forceUpdate) {
            $this->emailTemplateFromRawDataHydrator->hydrateFromArray($emailTemplate, $arrayData);
            $output->writeln(sprintf('"%s" updated', $emailTemplate->getName()));
        } else {
            $output->writeln(sprintf('"%s" updates skipped', $emailTemplate->getName()));
        }
    }

    /**
     * @param EmailTemplate $emailTemplate
     * @param array<string,mixed> $arrayData
     * @param OutputInterface $output
     */
    private function processNewTemplate(
        EmailTemplate $emailTemplate,
        array $arrayData,
        OutputInterface $output
    ): void {
        $this->emailTemplateFromRawDataHydrator->hydrateFromArray($emailTemplate, $arrayData);

        $emailTemplate->setOwner($this->getAdminUser());
        $emailTemplate->setOrganization($this->getOrganization());
        $this->doctrineHelper->getEntityManagerForClass(EmailTemplate::class)->persist($emailTemplate);

        $output->writeln(sprintf('"%s" created', $emailTemplate->getName()));
    }

    private function findExistingTemplate(string $name): ?EmailTemplate
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(EmailTemplate::class)->findOneBy([
            'name' => $name,
        ]);
    }

    /**
     * @param string $source
     *
     * @return array<string,array{path: string, format: string}>
     */
    private function getRawTemplates(string $source): array
    {
        if (is_dir($source)) {
            $finder = new Finder();
            $sources = $finder->files()->in($source);
        } else {
            $sources = [new SplFileInfo($source, '', '')];
        }

        $templates = [];
        foreach ($sources as $eachSource) {
            $fileName = str_replace(['.html.twig', '.html', '.txt.twig', '.txt'], '', $eachSource->getFilename());

            $format = 'html';
            if (preg_match('#\.(html|txt)(\.twig)?#', $eachSource->getFilename(), $match)) {
                $format = $match[1];
            }

            $templates[$fileName] = [
                'path' => $eachSource->getPath() . DIRECTORY_SEPARATOR . $eachSource->getFilename(),
                'format' => $format,
            ];
        }

        return $templates;
    }

    private function bcProcessTemplates(array $templates, InputInterface $input, OutputInterface $output): void
    {
        foreach ($templates as $fileName => $file) {
            $template = file_get_contents($file['path']);
            $parsedTemplate = EmailTemplate::parseContent($template);
            $templateName = $parsedTemplate['params']['name'] ?? $fileName;
            $existingTemplate = $this->findExistingTemplate($templateName);

            if ($existingTemplate) {
                if ($input->getOption('force')) {
                    $output->writeln(sprintf('"%s" updated', $existingTemplate->getName()));
                    $this->updateExistingTemplate($existingTemplate, $parsedTemplate);
                } else {
                    $output->writeln(sprintf('"%s" updates skipped', $existingTemplate->getName()));
                }
            } else {
                $this->loadNewTemplate($fileName, $file);
            }
        }
    }

    /**
     * @param string $fileName
     * @param array $file
     */
    protected function loadNewTemplate($fileName, $file)
    {
        $template = file_get_contents($file['path']);
        $emailTemplate = new EmailTemplate($fileName, $template, $file['format']);
        $emailTemplate->setOwner($this->getAdminUser());
        $emailTemplate->setOrganization($this->getOrganization());
        $this->doctrineHelper->getEntityManagerForClass(EmailTemplate::class)->persist($emailTemplate);
    }

    protected function updateExistingTemplate(EmailTemplate $emailTemplate, array $template)
    {
        $emailTemplate->setContent($template['content']);
        foreach ($template['params'] as $param => $value) {
            $setter = sprintf('set%s', ucfirst($param));
            $emailTemplate->$setter($value);
        }
    }

    /**
     * @return Organization
     */
    protected function getOrganization()
    {
        if ($this->organization) {
            return $this->organization;
        }

        /** @var OrganizationRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepositoryForClass(Organization::class);
        $this->organization = $repo->getFirst();

        return $this->organization;
    }

    /**
     * Get administrator user
     *
     * @return User
     *
     * @throws \RuntimeException
     */
    protected function getAdminUser()
    {
        if ($this->adminUser) {
            return $this->adminUser;
        }

        /** @var RoleRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(Role::class);
        /** @var Role $role */
        $role = $repository->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);

        if (!$role) {
            throw new \RuntimeException('Administrator role should exist.');
        }

        $user = $repository->getFirstMatchedUser($role);

        if (!$user) {
            throw new \RuntimeException(
                'Administrator user should exist to load email templates.'
            );
        }

        $this->adminUser = $user;

        return $this->adminUser;
    }
}
