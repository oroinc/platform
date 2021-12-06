<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\DataFixtures\LoadJobData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class JobControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([LoadJobData::class]);
    }

    public function testShouldInterruptRootJobAndAllActiveChildrenJobs(): void
    {
        /** @var Job $rootJob */
        $rootJob = $this->getReference(LoadJobData::JOB_1);
        /** @var Job $childJob */
        $childJob = $this->getReference(LoadJobData::JOB_2);

        $this->client->jsonRequest(
            'POST',
            $this->getUrl('oro_api_message_queue_job_interrupt_root_job', ['id' => $rootJob->getId()])
        );

        $response = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($response, 200);

        $jsonContent = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $expectedContent = [
            'successful' => true,
            'message' => 'Job interrupted',
        ];

        $this->assertEquals($expectedContent, $jsonContent);

        $rootJob = $this->getJobRepository()->findJobById($rootJob->getId());
        $this->assertTrue($rootJob->isInterrupted());
        $this->assertNotNull($rootJob->getStoppedAt());

        $childJob = $this->getJobRepository()->findJobById($childJob->getId());

        // only child job will have status cancelled, cause root status is calculated via MQ
        $this->assertSame(Job::STATUS_CANCELLED, $childJob->getStatus());
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataFixturesExecutorEntityManager(): EntityManagerInterface
    {
        return $this->getJobEntityManager();
    }

    private function getJobEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(Job::class);
    }

    private function getJobRepository(): JobRepository
    {
        return $this->getJobEntityManager()->getRepository(Job::class);
    }
}
