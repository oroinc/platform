<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\StatusCodeConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;

class StatusCodesConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testToArrayAndClone()
    {
        $config = new StatusCodesConfig();
        $code1 = new StatusCodeConfig();
        $code1->setExcluded();
        $code2 = new StatusCodeConfig();

        $config->addCode('code1', $code1);
        $config->addCode('code2', $code2);

        self::assertSame(
            [
                'code1' => ['exclude' => true],
                'code2' => null
            ],
            $config->toArray()
        );

        $cloneConfig = clone $config;
        self::assertEquals($config, $cloneConfig);
        self::assertNotSame($config->getCode('code1'), $cloneConfig->getCode('code1'));
        self::assertEquals($config->getCode('code1'), $cloneConfig->getCode('code1'));
        self::assertNotSame($config->getCode('code2'), $cloneConfig->getCode('code2'));
        self::assertEquals($config->getCode('code2'), $cloneConfig->getCode('code2'));
    }

    public function testCodes()
    {
        $config = new StatusCodesConfig();
        self::assertFalse($config->hasCodes());
        self::assertEquals([], $config->getCodes());
        self::assertTrue($config->isEmpty());
        self::assertEquals([], $config->toArray());

        $code1 = $config->addCode('code1');
        self::assertTrue($config->hasCodes());
        self::assertTrue($config->hasCode('code1'));
        self::assertEquals(['code1' => $code1], $config->getCodes());
        self::assertSame($code1, $config->getCode('code1'));
        self::assertFalse($config->isEmpty());
        self::assertEquals(['code1' => null], $config->toArray());
        $code1->setExcluded();
        self::assertEquals(['code1' => ['exclude' => true]], $config->toArray());

        $config->removeCode('code1');
        self::assertFalse($config->hasCodes());
        self::assertFalse($config->hasCode('code1'));
        self::assertEquals([], $config->getCodes());
        self::assertTrue($config->isEmpty());
        self::assertEquals([], $config->toArray());
    }

    public function testAddCode()
    {
        $config = new StatusCodesConfig();

        $code = $config->addCode('code');
        self::assertSame($code, $config->getCode('code'));

        $code1 = new StatusCodeConfig();
        $code1 = $config->addCode('code', $code1);
        self::assertSame($code1, $config->getCode('code'));
        self::assertNotSame($code, $code1);
    }
}
