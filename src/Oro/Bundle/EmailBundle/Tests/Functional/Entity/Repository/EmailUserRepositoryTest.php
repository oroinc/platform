<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailUserRepositoryTest extends WebTestCase
{
    /** @var DoctrineHelper */
    protected $doctrineHeler;

    /** @var EmailOriginHelper */
    protected $emailOriginHelper;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadEmailData::class]);

        $container = $this->getContainer();

        $this->doctrineHeler = $container->get('oro_entity.doctrine_helper');
        $this->emailOriginHelper = $container->get('oro_email.tools.email_origin_helper');
    }

    public function testGetIdsFromOrigin()
    {
        $owner = $this->getReference('simple_user');
        $origin = $this->emailOriginHelper->getEmailOrigin($owner->getEmail());

        /** @var EmailUserRepository $repo */
        $repo = $this->doctrineHeler->getEntityRepositoryForClass(EmailUser::class);

        $result = $repo->getIdsFromOrigin($origin);
        $this->assertCount(10, $result);

        for ($i = 1; $i <= 10; $i++) {
            $email = $this->getReference('emailUser_' . $i);

            $this->assertNotNull($email);

            $this->assertContains($email->getId(), $result);
        }
    }
}
