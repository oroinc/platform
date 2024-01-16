<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadEmailWithoutActivityData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadUserData::class, LoadUser::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $this->loadEmails($manager, $this->loadEmailTemplates());
        $manager->flush();
    }

    private function loadEmailTemplates(): array
    {
        $dictionaryDir = $this->container
            ->get('kernel')
            ->locateResource('@OroEmailBundle/Tests/Functional/DataFixtures/Data');

        $templates = [];
        $handle = fopen($dictionaryDir . DIRECTORY_SEPARATOR. 'emails.csv', 'r');
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

    private function loadEmails(ObjectManager $manager, array $templates): void
    {
        $emailEntityBuilder = $this->container->get('oro_email.email.entity.builder');
        $emailOriginHelper = $this->container->get('oro_email.tools.email_origin_helper');

        foreach ($templates as $index => $template) {
            $owner = $this->getReference('simple_user');
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

            $emailUser->addFolder($origin->getFolder(FolderType::SENT));
            $emailUser->getEmail()->addActivityTarget($owner);
            $emailUser->getEmail()->setHead(true);
            $emailUser->setOrganization($owner->getOrganization());
            $emailUser->setOwner($owner);
            $emailUser->setOrigin($origin);

            $emailBody = $emailEntityBuilder->body(
                "Hi,\n" . $template['Text'],
                false,
                true
            );

            $emailUser->getEmail()->setEmailBody($emailBody);
            $emailUser->getEmail()->setMessageId(sprintf('<id+&?= %s@%s>', $index, 'bap.migration.generated'));
            $this->setReference('email_' . ($index + 1), $emailUser->getEmail());
            $this->setReference('emailUser_' . ($index + 1), $emailUser);
            $this->setReference('emailBody_' . ($index + 1), $emailBody);
        }

        $emailUser->setOwner($this->getReference(LoadUser::USER));
        $this->setReference('emailUser_for_mass_mark_test', $emailUser);

        $emailEntityBuilder->getBatch()->persist($manager);
    }
}
