<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class LoadAjaxEmailControllerData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadUserData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $owner = $this->getReference('simple_user');

        $validTemplate = new EmailTemplate('valid_template', 'Valid template content {{ entity.email }}');
        $validTemplate->setSubject('Valid Subject {{ entity.username }}');
        $validTemplate->setEntityName(User::class);
        $validTemplate->setOrganization($owner->getOrganization());
        $validTemplate->setType('html');

        $invalidTemplate = new EmailTemplate('invalid_template', 'Invalid template {{ unclosed.tag');
        $invalidTemplate->setSubject('Invalid Subject {{ invalid.syntax');
        $invalidTemplate->setEntityName(User::class);
        $invalidTemplate->setOrganization($owner->getOrganization());
        $invalidTemplate->setType('html');

        $templateWithAttachments = new EmailTemplate('template_with_attachments', 'Template with attachments');
        $templateWithAttachments->setSubject('Subject with attachments');
        $templateWithAttachments->setEntityName(User::class);
        $templateWithAttachments->setOrganization($owner->getOrganization());
        $templateWithAttachments->setType('html');

        /** @var FileManager $fileManager */
        $fileManager = $this->container->get('oro_attachment.file_manager');

        $file = $fileManager->createFileEntity(
            __DIR__ . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'dummy.pdf'
        );
        $file->setOwner($owner);
        $manager->persist($file);

        $emailTemplateAttachment = new EmailTemplateAttachment();
        $emailTemplateAttachment->setFile($file);

        $templateWithAttachments->addAttachment($emailTemplateAttachment);

        $manager->persist($validTemplate);
        $manager->persist($invalidTemplate);
        $manager->persist($templateWithAttachments);
        $manager->flush();

        $this->setReference('valid_email_template', $validTemplate);
        $this->setReference('invalid_email_template', $invalidTemplate);
        $this->setReference('email_template_with_attachments', $templateWithAttachments);
        $this->setReference('email_template_attachment', $emailTemplateAttachment);
    }
}
