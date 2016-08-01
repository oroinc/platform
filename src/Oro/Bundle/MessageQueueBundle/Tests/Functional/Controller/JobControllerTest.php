<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Controller;

use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class JobControllerTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->startTransaction();
    }

    protected function tearDown()
    {
        $this->rollbackTransaction();
    }

    public function testShouldRenderListOfRootJobs()
    {
        $this->client->request('GET', $this->getUrl('oro_message_queue_root_jobs'));

        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
    }

    public function testShouldRenderListOfChildJobs()
    {
        $rootJob = new Job();
        $rootJob->setName('root-job');
        $rootJob->setStatus('');
        $rootJob->setCreatedAt(new \DateTime());

        $this->getEntityManager()->persist($rootJob);
        $this->getEntityManager()->flush();

        $this->client->request('GET', $this->getUrl('oro_message_queue_child_jobs', ['id' => $rootJob->getId()]));

        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.default_entity_manager');
    }
}
