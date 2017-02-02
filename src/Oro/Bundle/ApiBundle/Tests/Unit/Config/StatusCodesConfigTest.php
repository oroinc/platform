<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\StatusCodeConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;

class StatusCodesConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testToArrayAndClone()
    {
        $config = new StatusCodesConfig();
        $code1 = new StatusCodeConfig();
        $code1->setExcluded();
        $code2 = new StatusCodeConfig();

        $config->addCode('code1', $code1);
        $config->addCode('code2', $code2);

        $this->assertSame(
            [
                'code1' => ['exclude' => true],
                'code2' => null
            ],
            $config->toArray()
        );

        $cloneConfig = clone $config;
        $this->assertEquals($config, $cloneConfig);
        $this->assertNotSame($config->getCode('code1'), $cloneConfig->getCode('code1'));
        $this->assertEquals($config->getCode('code1'), $cloneConfig->getCode('code1'));
        $this->assertNotSame($config->getCode('code2'), $cloneConfig->getCode('code2'));
        $this->assertEquals($config->getCode('code2'), $cloneConfig->getCode('code2'));
    }

    public function testCodes()
    {
        $config = new StatusCodesConfig();
        $this->assertFalse($config->hasCodes());
        $this->assertEquals([], $config->getCodes());
        $this->assertTrue($config->isEmpty());
        $this->assertEquals([], $config->toArray());

        $code1 = $config->addCode('code1');
        $this->assertTrue($config->hasCodes());
        $this->assertTrue($config->hasCode('code1'));
        $this->assertEquals(['code1' => $code1], $config->getCodes());
        $this->assertSame($code1, $config->getCode('code1'));
        $this->assertFalse($config->isEmpty());
        $this->assertEquals(['code1' => null], $config->toArray());
        $code1->setExcluded();
        $this->assertEquals(['code1' => ['exclude' => true]], $config->toArray());

        $config->removeCode('code1');
        $this->assertFalse($config->hasCodes());
        $this->assertFalse($config->hasCode('code1'));
        $this->assertEquals([], $config->getCodes());
        $this->assertTrue($config->isEmpty());
        $this->assertEquals([], $config->toArray());
    }

    public function testAddCode()
    {
        $config = new StatusCodesConfig();

        $code = $config->addCode('code');
        $this->assertSame($code, $config->getCode('code'));

        $code1 = new StatusCodeConfig();
        $code1 = $config->addCode('code', $code1);
        $this->assertSame($code1, $config->getCode('code'));
        $this->assertNotSame($code, $code1);
    }
}
