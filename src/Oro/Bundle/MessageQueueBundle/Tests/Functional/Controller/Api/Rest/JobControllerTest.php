<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class JobControllerTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
    }

    public function testShouldInterruptRootJob()
    {
        $rootJob = new Job();
        $rootJob->setName('root-job');
        $rootJob->setStatus('');
        $rootJob->setCreatedAt(new \DateTime());

        $this->getEntityManager()->persist($rootJob);
        $this->getEntityManager()->flush();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_message_queue_job_interrupt_root_job', ['id' => $rootJob->getId()])
        );

        $response = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($response, 200);

        $jsonContent = json_decode($response->getContent(), true);

        $expectedContent = [
            'successful' => true,
            'message' => 'Job interrupted',
        ];

        $this->assertEquals($expectedContent, $jsonContent);

        $this->getEntityManager()->refresh($rootJob);
        $this->assertTrue($rootJob->isInterrupted());
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.message_queue_job_entity_manager');
    }
}
