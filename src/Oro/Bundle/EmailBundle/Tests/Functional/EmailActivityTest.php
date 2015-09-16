<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EmailActivityTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());
        $this->loadFixtures(['Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData']);
    }

    public function testActivityDateIsNotUpdatedAfterUpdateEntity()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $email = $this->getReference('email_1');
        $originalSentAt = $email->getSentAt();
        $email->setSubject('My Web Store Introduction Changed');
        $em->flush($email);

        $activityList = $em
            ->getRepository(ActivityList::ENTITY_NAME)
            ->findOneBy(
                [
                    'relatedActivityClass' => 'Oro\Bundle\EmailBundle\Entity\Email',
                    'relatedActivityId' => $email->getId()
                ]
            );

        $this->assertEquals($originalSentAt, $activityList->getUpdatedAt());
    }
}
