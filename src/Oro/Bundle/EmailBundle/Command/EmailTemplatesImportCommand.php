<?php
declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command;

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

    private Organization $organization;

    private User $adminUser;

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
            ->addArgument('source', InputArgument::REQUIRED, "Folder or File to import")
            ->addOption('force', null, InputOption::VALUE_NONE, "Force update");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getArgument('source');

        if ((!is_dir($source) && !is_file($source)) || !is_readable($source)) {
            $output->writeln(sprintf('<error>Source path "%s" should exist and be readable</error>', $source));

            return 1;
        }

        $templates = $this->getRawTemplates($source);
        $output->writeln(sprintf('Found %d templates', count($templates)));

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

        $this->doctrineHelper->getEntityManagerForClass(EmailTemplate::class)->flush();

        return 0;
    }

    /**
     * @param $name
     *
     * @return null|EmailTemplate
     */
    private function findExistingTemplate($name)
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(EmailTemplate::class)->findOneBy([
            'name' => $name,
        ]);
    }

    /**
     * @param $source
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    private function getRawTemplates($source)
    {
        if (is_dir($source)) {
            $finder = new Finder();
            $sources = $finder->files()->in($source);
        } else {
            $sources = [new SplFileInfo($source, '', '')];
        }

        $templates = [];
        /** @var SplFileInfo $file */
        foreach ($sources as $source) {
            $fileName = str_replace(['.html.twig', '.html', '.txt.twig', '.txt'], '', $source->getFilename());

            $format = 'html';
            if (preg_match('#\.(html|txt)(\.twig)?#', $source->getFilename(), $match)) {
                $format = $match[1];
            }

            $templates[$fileName] = [
                'path' => $source->getPath() . DIRECTORY_SEPARATOR . $source->getFilename(),
                'format' => $format,
            ];
        }

        return $templates;
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

    /**
     * @param EmailTemplate $emailTemplate
     * @param array $template
     */
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
