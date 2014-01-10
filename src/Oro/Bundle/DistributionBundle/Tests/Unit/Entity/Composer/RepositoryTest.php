<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Entity\Composer;


use Oro\Bundle\DistributionBundle\Entity\Composer\Repository;

class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldAllowSetUrl()
    {
        $repo = new Repository();
        $repo->setUrl('http://mysite.com');
    }

    /**
     * @test
     */
    public function shouldReturnUrlThatWasSetBefore()
    {
        $repo = new Repository();
        $repo->setUrl($url = 'http://mysite.com');
        $this->assertEquals($url, $repo->getUrl());
    }

    /**
     * @test
     */
    public function shouldAllowSetType()
    {
        $repo = new Repository();
        $repo->setType('pear');
    }

    /**
     * @test
     */
    public function shouldReturnTypeThatWasSetBefore()
    {
        $repo = new Repository();
        $repo->setType($type = 'vcs');
        $this->assertEquals($type, $repo->getType());
    }
}
 