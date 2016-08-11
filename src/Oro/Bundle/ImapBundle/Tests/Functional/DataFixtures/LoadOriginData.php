<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Symfony\Component\PropertyAccess\PropertyAccess;

class LoadOriginData extends AbstractFixture
{
    /**
     * @var array Channels configuration
     */
    protected $data = [
        [
            'mailboxName' => 'Test Mailbox',
            'reference' => 'origin_one',
        ],
        [
            'mailboxName' => 'Test Mailbox 2',
            'reference' => 'origin_two',
            'folder' => [
                'name' => 'Folder 1',
                'type' => 'inbox',
                'enabled' => true,
            ]
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        foreach ($this->data as $data) {
            $origin = new UserEmailOrigin();
            $origin->setOrganization($organization);
            $this->setEntityPropertyValues($origin, $data, ['reference', 'folder']);
            $this->setReference($data['reference'], $origin);
            $manager->persist($origin);

            if (array_key_exists('folder', $data)) {
                $folder = new EmailFolder();
                $folder->setOrigin($origin);
                $folder->setSyncEnabled($data['folder']['enabled']);
                $folder->setFullName($data['folder']['name']);
                $folder->setName($data['folder']['name']);
                $folder->setType($data['folder']['type']);
                $manager->persist($folder);
                $imapFolder = new ImapEmailFolder();
                $imapFolder->setFolder($folder);
                $imapFolder->setUidValidity(1);
                $manager->persist($imapFolder);

                $origin->addFolder($folder);
            }
        }

        $manager->flush();
    }

    /**
     * @param object $entity
     * @param array $data
     * @param array $excludeProperties
     */
    public function setEntityPropertyValues($entity, array $data, array $excludeProperties = [])
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $property => $value) {
            if (in_array($property, $excludeProperties)) {
                continue;
            }
            $propertyAccessor->setValue($entity, $property, $value);
        }
    }
}
