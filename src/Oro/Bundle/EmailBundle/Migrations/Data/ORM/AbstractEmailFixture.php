<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

abstract class AbstractEmailFixture extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var User|null */
    protected $adminUser;

    /** @var Organization|null */
    protected $organization;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData',
        ];
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
            $files = array();
        }

        $templates = array();
        /** @var \Symfony\Component\Finder\SplFileInfo $file  */
        foreach ($files as $file) {
            $fileName = str_replace(array('.html.twig', '.html', '.txt.twig', '.txt'), '', $file->getFilename());

            $format = 'html';
            if (preg_match('#\.(html|txt)(\.twig)?#', $file->getFilename(), $match)) {
                $format = $match[1];
            }

            $templates[$fileName] = array(
                'path'   => $file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename(),
                'format' => $format,
            );
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
     * @param ObjectManager $manager
     * @param array $template
     *
     * @return EmailTemplate|null
     */
    protected function findExistingTemplate(ObjectManager $manager, array $template)
    {
        return null;
    }

    /**
     * @param ObjectManager $manager
     * @return Organization
     */
    protected function getOrganization(ObjectManager $manager)
    {
        if ($this->organization) {
            return $this->organization;
        }

        $this->organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        return $this->organization;
    }

    /**
     * Get administrator user
     *
     * @param ObjectManager $manager
     *
     * @return User
     *
     * @throws \RuntimeException
     */
    protected function getAdminUser(ObjectManager $manager)
    {
        if ($this->adminUser) {
            return $this->adminUser;
        }

        $repository = $manager->getRepository('OroUserBundle:Role');
        $role       = $repository->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);

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

    /**
     * Return path to email templates
     *
     * @return string
     */
    abstract public function getEmailsDir();
}
