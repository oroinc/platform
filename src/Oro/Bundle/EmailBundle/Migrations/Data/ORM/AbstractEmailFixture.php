<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Symfony\Component\Finder\Finder;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

abstract class AbstractEmailFixture extends AbstractFixture implements DependentFixtureInterface
{
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
        $adminUser = $this->getAdminUser($manager);
        $organization = $this->getOrganization($manager);

        $emailTemplates = $this->getEmailTemplatesList($this->getEmailsDir());

        foreach ($emailTemplates as $fileName => $file) {
            $template = file_get_contents($file['path']);
            $emailTemplate = new EmailTemplate($fileName, $template, $file['format']);
            $emailTemplate->setOwner($adminUser);
            $emailTemplate->setOrganization($organization);
            $manager->persist($emailTemplate);
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
            if (preg_match('#/([\w]+Bundle)/#', $file->getPath(), $match)) {
                $fileName = $match[1] . ':' . $fileName;
            }

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
     * @return Organization
     */
    protected function getOrganization(ObjectManager $manager)
    {
        return $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
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

        return $user;
    }

    /**
     * Return path to email templates
     *
     * @return string
     */
    abstract public function getEmailsDir();
}
