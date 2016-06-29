<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;

class LoadEmailWithoutActivityData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $templates;

    /**
     * @var EmailEntityBuilder
     */
    protected $emailEntityBuilder;

    /**
     * @var EmailOriginHelper
     */
    protected $emailOriginHelper;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadUserData',];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        if (!$container) {
            return;
        }

        $this->container = $container;
        $this->emailEntityBuilder = $container->get('oro_email.email.entity.builder');
        $this->emailOriginHelper = $container->get('oro_email.tools.email_origin_helper');
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $om)
    {
        $this->loadEmailTemplates();
        $this->loadEmailsDemo($om);
        $om->flush();
    }

    protected function loadEmailTemplates()
    {
        $dictionaryDir = $this->container
            ->get('kernel')
            ->locateResource('@OroEmailBundle/Tests/Functional/DataFixtures/Data');

        $handle = fopen($dictionaryDir . DIRECTORY_SEPARATOR. "emails.csv", "r");
        if ($handle) {
            $headers = [];
            if (($data = fgetcsv($handle, 1000, ",")) !== false) {
                //read headers
                $headers = $data;
            }
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $this->templates[] = array_combine($headers, array_values($data));
            }
        }
    }

    /**
     * @param ObjectManager $om
     */
    protected function loadEmailsDemo(ObjectManager $om)
    {
        $adminUser = $om->getRepository('OroUserBundle:User')->findOneByUsername('admin');

        foreach ($this->templates as $index => $template) {
            $owner = $this->getReference('simple_user');
            $origin = $this->emailOriginHelper->getEmailOrigin($owner->getEmail());

            $emailUser = $this->emailEntityBuilder->emailUser(
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

            $emailBody = $this->emailEntityBuilder->body(
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

        $emailUser->setOwner($adminUser);
        $this->setReference('emailUser_for_mass_mark_test', $emailUser);

        $this->emailEntityBuilder->getBatch()->persist($om);
    }
}
