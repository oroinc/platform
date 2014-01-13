<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Entity\Composer;


use Oro\Bundle\DistributionBundle\Entity\Composer\Config;
use Oro\Bundle\DistributionBundle\Entity\Composer\Repository;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\MockHelperTrait;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    use MockHelperTrait;

    /**
     * @test
     */
    public function shouldBeConstructedWithJsonFile()
    {
        new Config($this->createJsonFileMock());
    }

    /**
     * @test
     */
    public function shouldAllowSetGithubOauthToken()
    {
        $config = new Config($this->createJsonFileMock());
        $config->setOauth(uniqid());
    }

    /**
     * @test
     */
    public function shouldReturnGithubOauthTokenThatWasSetBefore()
    {
        $config = new Config($this->createJsonFileMock());
        $config->setOauth($token = uniqid());

        $this->assertEquals($token, $config->getOauth());
    }

    /**
     * @test
     */
    public function shouldAllowSetRepositories()
    {
        $config = new Config($this->createJsonFileMock());
        $config->setRepositories([new Repository()]);
    }

    /**
     * @test
     */
    public function shouldReturnRepositoriesThatWereSetBefore()
    {
        $config = new Config($this->createJsonFileMock());
        $config->setRepositories($repos = [new Repository(), new Repository()]);

        $this->assertEquals($repos, $config->getRepositories());
    }

    /**
     * @test
     */
    public function shouldLoadOauthFromGivenJsonFile()
    {
        $data = [
            'config' => ['github-oauth' => ['github.com' => uniqid()]]
        ];
        $config = new Config($this->createJsonFileMock($data));

        $this->assertEquals($data['config']['github-oauth']['github.com'], $config->getOauth());
    }

    /**
     * @test
     */
    public function shouldLoadRepositoriesFromGivenJsonFile()
    {
        $data = [
            'repositories' => [
                ['type' => 'vcs', 'url' => 'myvcs.com'],
                ['type' => 'composer', 'url' => 'my-packagist.com'],
            ]
        ];
        $config = new Config($this->createJsonFileMock($data));

        $repos = $config->getRepositories();
        $this->assertCount(2, $repos);

        $this->assertInstanceOf('Oro\Bundle\DistributionBundle\Entity\Composer\Repository', $repos[0]);
        $this->assertEquals($data['repositories'][0]['type'], $repos[0]->getType());
        $this->assertEquals($data['repositories'][0]['url'], $repos[0]->getUrl());

        $this->assertInstanceOf('Oro\Bundle\DistributionBundle\Entity\Composer\Repository', $repos[1]);
        $this->assertEquals($data['repositories'][1]['type'], $repos[1]->getType());
        $this->assertEquals($data['repositories'][1]['url'], $repos[1]->getUrl());
    }

    /**
     * @test
     */
    public function shouldFlushRepositories()
    {
        $file = $this->createJsonFileMock();
        $config = new Config($file);

        $repository = new Repository();
        $repository->setUrl('myurl');
        $repository->setType('composer');

        $config->setRepositories([$repository]);

        $file->expects($this->once())
            ->method('write')
            ->with(['repositories' => [['type' => 'composer', 'url' => 'myurl']]]);

        $config->flush();
    }

    /**
     * @test
     */
    public function shouldFlushOauth()
    {
        $file = $this->createJsonFileMock();
        $config = new Config($file);
        $config->setOauth($oauth = uniqid());

        $file->expects($this->once())
            ->method('write')
            ->with(['config' => ['github-oauth' => ['github.com' => $oauth]], 'repositories' => []]);

        $config->flush();
    }

    /**
     * @test
     */
    public function shouldUnsetOauthDuringFlushIfOauthIsNull()
    {
        $file = $this->createJsonFileMock(['config' => ['github-oauth' => ['github.com' => uniqid()]]]);
        $config = new Config($file);
        $config->setOauth(null);

        $file->expects($this->once())
            ->method('write')
            ->with(['config' => ['github-oauth' => new \stdClass()], 'repositories' => []]);

        $config->flush();
    }

    /**
     * @param array $data
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createJsonFileMock($data = [])
    {
        $mock = $this->createConstructorLessMock('Composer\Json\JsonFile');
        $mock->expects($this->any())
            ->method('read')
            ->will($this->returnValue($data));

        return $mock;
    }
}
