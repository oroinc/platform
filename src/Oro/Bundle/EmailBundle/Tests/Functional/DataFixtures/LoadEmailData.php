<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class LoadEmailData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadUserData::class, LoadUser::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $dataDir = $this->container
            ->get('kernel')
            ->locateResource('@OroEmailBundle/Tests/Functional/DataFixtures/Data');
        $this->loadEmails($manager, $dataDir, $this->loadEmailTemplates($dataDir));
        $manager->flush();
    }

    private function loadEmailTemplates(string $dataDir): array
    {
        $templates = [];
        $handle = fopen($dataDir . DIRECTORY_SEPARATOR . 'emails.csv', 'r');
        if ($handle) {
            $headers = [];
            if (($data = fgetcsv($handle, 1000, ",")) !== false) {
                //read headers
                $headers = $data;
            }
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $templates[] = array_combine($headers, array_values($data));
            }
        }

        return $templates;
    }

    private function loadEmails(ObjectManager $manager, string $dataDir, array $templates): void
    {
        $emailEntityBuilder = $this->container->get('oro_email.email.entity.builder');
        $emailOriginHelper = $this->container->get('oro_email.tools.email_origin_helper');
        $attachmentContent = base64_encode(file_get_contents($dataDir . DIRECTORY_SEPARATOR . 'test.png'));

        foreach ($templates as $index => $template) {
            $owner = $this->getEmailOwner();
            $origin = $emailOriginHelper->getEmailOrigin($owner->getEmail());

            $emailUser = $emailEntityBuilder->emailUser(
                $template['Subject'],
                $owner->getEmail(),
                $owner->getEmail(),
                new \DateTime($template['SentAt']),
                new \DateTime('now'),
                new \DateTime('now'),
                Email::NORMAL_IMPORTANCE,
                "cc{$index}@example.com",
                "bcc{$index}@example.com"
            );

            $emailUser->addFolder($this->getFolder($origin));
            $emailUser->getEmail()->addActivityTarget($owner);
            $emailUser->getEmail()->addActivityTarget($this->getReference('simple_user2'));
            $emailUser->getEmail()->setHead(true);
            $emailUser->setOrganization($owner->getOrganization());
            $emailUser->setOwner($owner);
            $emailUser->setOrigin($origin);

            $emailBody = $emailEntityBuilder->body(
                "Hi,\n" . $template['Text'],
                false,
                true
            );

            $attachment = $emailEntityBuilder->attachment('test.png', 'image/png');
            $attachment->setContent($emailEntityBuilder->attachmentContent($attachmentContent, 'base64'));

            $emailBody->addAttachment($attachment);

            $emailUser->getEmail()->setEmailBody($emailBody);
            $emailUser->getEmail()->setMessageId(sprintf('<id+&?= %s@%s>', $index, 'bap.migration.generated'));
            $this->setReference('email_' . ($index + 1), $emailUser->getEmail());
            $this->setReference('emailUser_' . ($index + 1), $emailUser);
            $this->setReference('emailBody_' . ($index + 1), $emailBody);
            $this->setReference('emailAttachment_' . ($index + 1), $attachment);
        }

        $emailUser->setOwner($this->getReference(LoadUser::USER));
        $this->setReference('emailUser_for_mass_mark_test', $emailUser);

        $emailEntityBuilder->getBatch()->persist($manager);
    }

    /**
     * Gets an user that should be set as email user owner.
     */
    protected function getEmailOwner(): User
    {
        return $this->getReference('simple_user');
    }

    protected function getFolder(EmailOrigin $origin): EmailFolder
    {
        return $origin->getFolder(FolderType::SENT);
    }
}
