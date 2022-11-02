<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Entity;

use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Job\Job;

class JobInstanceTest extends \PHPUnit\Framework\TestCase
{
    private const CONNECTOR = 'acme_connector';
    private const TYPE = 'export';
    private const ALIAS = 'acme_job_alias';

    private JobInstance $jobInstance;

    protected function setUp(): void
    {
        $this->jobInstance = new JobInstance(self::CONNECTOR, self::TYPE, self::ALIAS);
    }

    public function testGetId(): void
    {
        self::assertNull($this->jobInstance->getId());
    }

    public function testGetSetCode(): void
    {
        self::assertNull($this->jobInstance->getCode());

        $expectedCode = 'expected_code';
        $this->jobInstance->setCode($expectedCode);
        self::assertEquals($expectedCode, $this->jobInstance->getCode());
    }

    public function testGetSetLabel(): void
    {
        self::assertNull($this->jobInstance->getLabel());

        $expectedLabel = 'expected label';
        $this->jobInstance->setLabel($expectedLabel);
        self::assertEquals($expectedLabel, $this->jobInstance->getLabel());
    }

    public function testGetConnector(): void
    {
        self::assertEquals(self::CONNECTOR, $this->jobInstance->getConnector());
    }

    public function testGetAlias(): void
    {
        self::assertEquals(self::ALIAS, $this->jobInstance->getAlias());
    }

    public function testGetSetStatus(): void
    {
        self::assertSame(0, $this->jobInstance->getStatus());

        $expectedStatus = 1;
        $this->jobInstance->setStatus($expectedStatus);
        self::assertEquals($expectedStatus, $this->jobInstance->getStatus());
    }

    public function testGetSetType(): void
    {
        self::assertEquals(self::TYPE, $this->jobInstance->getType());

        $expectedType = 'import';
        $this->jobInstance->setType($expectedType);
        self::assertEquals($expectedType, $this->jobInstance->getType());
    }

    public function testGetSetRawConfiguration(): void
    {
        self::assertEmpty($this->jobInstance->getRawConfiguration());

        $expectedConfiguration = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'];

        $this->jobInstance->setRawConfiguration($expectedConfiguration);
        self::assertEquals($expectedConfiguration, $this->jobInstance->getRawConfiguration());
    }

    public function testGetSetJob(): void
    {
        self::assertEmpty($this->jobInstance->getJob());

        $expectedConfiguration = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'];

        $job = $this->createMock(Job::class);
        $job
            ->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($expectedConfiguration);

        $this->jobInstance->setJob($job);
        self::assertEquals($expectedConfiguration, $this->jobInstance->getRawConfiguration());
        self::assertEquals($job, $this->jobInstance->getJob());
    }
}
