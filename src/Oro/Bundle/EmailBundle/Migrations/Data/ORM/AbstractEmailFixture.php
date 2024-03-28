<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * The base class for fixtures that load email templates.
 */
abstract class AbstractEmailFixture extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    private ?User $adminUser = null;
    private ?Organization $organization = null;

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [LoadAdminUserData::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $emailTemplates = $this->getEmailTemplatesList($this->getEmailsDir());
        foreach ($emailTemplates as $fileName => $file) {
            $this->loadTemplate($manager, $fileName, $file);
        }
        $manager->flush();
    }

    /**
     * @param string $dir
     * @return array
     */
    public function getEmailTemplatesList($dir)
    {
        if (is_dir($dir)) {
            $finder = new Finder();
            $files = $finder->files()->in($dir);
        } else {
            $files = [];
        }

        $templates = [];
        /** @var SplFileInfo $file  */
        foreach ($files as $file) {
            $fileName = $this->getBasename($file);

            $format = 'html';
            if (preg_match('#\.(html|txt)(\.twig)?#', $file->getFilename(), $match)) {
                $format = $match[1];
            }

            $templates[$fileName] = [
                'path'   => $file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename(),
                'format' => $format,
            ];
        }

        return $templates;
    }

    /**
     * @param ObjectManager $manager
     * @param string $fileName
     * @param array $file
     */
    protected function loadTemplate(ObjectManager $manager, $fileName, array $file)
    {
        $template = file_get_contents($file['path']);
        $parsedTemplate = EmailTemplate::parseContent($template);
        if (empty($parsedTemplate['params']['name'])) {
            throw new \LogicException(sprintf('Email template name is expected to be non empty in file %s', $fileName));
        }

        if ($parsedTemplate['params']['name'] !== $this->getBasename($fileName)) {
            throw new \LogicException(
                sprintf(
                    'Email template name is expected to be equal to its filename: "%s" is not equal to "%s"',
                    $parsedTemplate['params']['name'],
                    $this->getBasename($fileName)
                )
            );
        }

        $existingTemplate = $this->findExistingTemplate($manager, $parsedTemplate);

        if ($existingTemplate) {
            $this->updateExistingTemplate($existingTemplate, $parsedTemplate);
        } else {
            $this->loadNewTemplate($manager, $fileName, $file);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param string $fileName
     * @param array $file
     */
    protected function loadNewTemplate(ObjectManager $manager, $fileName, $file)
    {
        $template = file_get_contents($file['path']);
        $emailTemplate = new EmailTemplate($fileName, $template, $file['format']);
        $emailTemplate->setOwner($this->getAdminUser($manager));
        $emailTemplate->setOrganization($this->getOrganization($manager));
        $manager->persist($emailTemplate);
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
     * @param ObjectManager $manager
     * @param array $template
     *
     * @return EmailTemplate|null
     */
    protected function findExistingTemplate(ObjectManager $manager, array $template)
    {
        return null;
    }

    protected function getOrganization(ObjectManager $manager): Organization
    {
        if (null === $this->organization) {
            $this->organization = $manager->getRepository(Organization::class)->getFirst();
        }

        return $this->organization;
    }

    protected function getAdminUser(ObjectManager $manager): User
    {
        if (null === $this->adminUser) {
            $repository = $manager->getRepository(Role::class);
            $role = $repository->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);
            if (!$role) {
                throw new \RuntimeException('Administrator role should exist.');
            }
            $user = $repository->getFirstMatchedUser($role);
            if (!$user) {
                throw new \RuntimeException('Administrator user should exist to load email templates.');
            }
            $this->adminUser = $user;
        }

        return $this->adminUser;
    }

    protected function getBasename(SplFileInfo|string $file): string
    {
        $filename = $file instanceof SplFileInfo ? $file->getFilename() : $file;

        return str_replace(['.html.twig', '.html', '.txt.twig', '.txt'], '', $filename);
    }

    /**
     * Return path to email templates
     *
     * @return string
     */
    abstract public function getEmailsDir();
}
