<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Model;

use Behat\Gherkin\Node\FeatureNode;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider\CriteriaArrayCollection;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\FeatureStatistic;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\FeatureStatisticManager;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\StatisticRepository;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification\FeaturePathLocator;

class FeatureStatisticManagerTest extends \PHPUnit\Framework\TestCase
{
    private const BUILD_ID = '12345';
    private const GIT_TARGET = 'master';
    private const GIT_BRANCH = 'ticket/PL-0000';
    private const STAGE_NAME = 'TestEnv_42';
    private const JOB_NAME = 'dev_weekly/PR-28584';

    /** @var StatisticRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $featureRepository;

    /** @var FeaturePathLocator|\PHPUnit\Framework\MockObject\MockObject */
    private $featurePathLocator;

    /** @var CriteriaArrayCollection */
    private $criteria;

    /** @var FeatureStatisticManager */
    private $manager;

    protected function setUp(): void
    {
        $this->featureRepository = $this->createMock(StatisticRepository::class);
        $this->featurePathLocator = $this->createMock(FeaturePathLocator::class);
        $this->criteria = new CriteriaArrayCollection(
            [
                'build_id' => self::BUILD_ID,
                'target_branch' => self::GIT_TARGET,
                'branch_name' => self::GIT_BRANCH,
                'stage_name' => self::STAGE_NAME,
                'job_name' => self::JOB_NAME,
            ]
        );

        $this->manager = new FeatureStatisticManager(
            $this->featureRepository,
            $this->featurePathLocator,
            $this->criteria
        );
    }

    public function testAddStatistic(): void
    {
        $this->featurePathLocator->expects($this->once())
            ->method('getRelativePath')
            ->with('/path/to/file')
            ->willReturn('/relative/path/to/file');

        /** @var FeatureNode|\PHPUnit\Framework\MockObject\MockObject $feature */
        $feature = $this->createMock(FeatureNode::class);
        $feature->expects($this->once())
            ->method('getFile')
            ->willReturn('/path/to/file');

        $expected = new FeatureStatistic();
        $expected->setPath('/relative/path/to/file')
            ->setDuration(142)
            ->setGitBranch(self::GIT_BRANCH)
            ->setGitTarget(self::GIT_TARGET)
            ->setBuildId(self::BUILD_ID)
            ->setStageName(self::STAGE_NAME)
            ->setJobName(self::JOB_NAME);

        $this->featureRepository->expects($this->once())
            ->method('add')
            ->with($expected);

        $this->manager->addStatistic($feature, 142);
    }

    public function testGetTested(): void
    {
        $expected = new FeatureStatistic();
        $expected->setPath('/relative/path/to/file');

        $this->featureRepository->expects($this->once())
            ->method('findBy')
            ->with(
                [
                    'build_id' => self::BUILD_ID,
                    'git_target' => self::GIT_TARGET,
                    'git_branch' => self::GIT_BRANCH,
                    'stage_name' => self::STAGE_NAME,
                    'job_name' => self::JOB_NAME,
                ]
            )
            ->willReturn([$expected]);

        $this->assertEquals(['/relative/path/to/file'], $this->manager->getTested());
    }

    public function testGetTestedNoBuildId(): void
    {
        $this->featureRepository->expects($this->never())
            ->method('findBy');

        $this->criteria->set('build_id', null);

        $manager = new FeatureStatisticManager($this->featureRepository, $this->featurePathLocator, $this->criteria);

        $this->assertEquals([], $manager->getTested());
    }

    public function testGetTestedNoGitTarget(): void
    {
        $expected = new FeatureStatistic();
        $expected->setPath('/relative/path/to/file');

        $this->featureRepository->expects($this->once())
            ->method('findBy')
            ->with(
                [
                    'build_id' => self::BUILD_ID,
                    'git_target' => null,
                    'git_branch' => self::GIT_BRANCH,
                    'stage_name' => self::STAGE_NAME,
                    'job_name' => self::JOB_NAME,
                ]
            )
            ->willReturn([$expected]);

        $this->criteria->set('target_branch', null);

        $manager = new FeatureStatisticManager($this->featureRepository, $this->featurePathLocator, $this->criteria);

        $this->assertEquals(['/relative/path/to/file'], $manager->getTested());
    }

    public function testSaveStatistics(): void
    {
        $this->featureRepository->expects($this->once())
            ->method('flush');

        $this->manager->saveStatistics();
    }

    public function testCleanOldStatisticsForMasterBranch(): void
    {
        $this->featureRepository->expects($this->once())
            ->method('removeOldStatistics')
            ->with(2592000);

        $this->criteria->set('target_branch', null);
        $this->criteria->set('branch_name', 'master');

        $manager = new FeatureStatisticManager($this->featureRepository, $this->featurePathLocator, $this->criteria);
        $manager->cleanOldStatistics();
    }

    public function testCleanOldStatistics(): void
    {
        $this->featureRepository->expects($this->never())
            ->method('removeOldStatistics');

        $this->manager->cleanOldStatistics();
    }
}
