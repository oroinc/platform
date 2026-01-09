<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Form\Type\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class LoadEmailAttachmentTypeData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
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
        $dummyPdfFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'dummy.pdf';
        $owner = $this->getReference('simple_user');

        $fileManager = $this->container->get('oro_attachment.file_manager');
        $attachmentFile = $fileManager->createFileEntity($dummyPdfFile);
        $attachment = new Attachment();
        $attachment->setFile($attachmentFile);
        $attachment->setOwner($owner);
        $attachment->setOrganization($owner->getOrganization());
        $this->setReference('test_attachment', $attachment);

        $emailTemplateAttachment = new EmailTemplateAttachment();
        $emailTemplateAttachmentFile = $fileManager->createFileEntity($dummyPdfFile);
        $emailTemplateAttachment->setFile($emailTemplateAttachmentFile);
        $this->setReference('test_template_attachment', $emailTemplateAttachment);

        $manager->persist($attachment);
        $manager->persist($emailTemplateAttachment);
        $manager->flush();
    }
}
