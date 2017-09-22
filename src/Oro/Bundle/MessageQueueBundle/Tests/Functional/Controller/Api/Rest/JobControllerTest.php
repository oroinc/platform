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

    public function testShouldInterruptRootJobAndAllActiveChildrenJobs()
    {
        $childJob = new Job();
        $childJob->setName('child-job');
        $childJob->setStatus(Job::STATUS_RUNNING);
        $childJob->setCreatedAt(new \DateTime());

        $rootJob = new Job();
        $rootJob->setName('root-job');
        $rootJob->setStatus('');
        $rootJob->setCreatedAt(new \DateTime());
        $rootJob->setChildJobs([$childJob]);
        $childJob->setRootJob($rootJob);

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
        $this->assertNotNull($rootJob->getStoppedAt());
        // only child job will have status cancelled, cause root status is calculated via MQ
        $this->assertSame(Job::STATUS_CANCELLED, $childJob->getStatus());
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(Job::class);
    }
}
