<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\AssetBundle\NodeProcessFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class NodeProcessFactoryTest extends TestCase
{
    public function testCreateProcess()
    {
        $serverPath = $_SERVER['PATH'];
        $_SERVER['PATH'] = null;

        $factory = new NodeProcessFactory('test_engine_path');
        $actual = $factory->createProcess('bin/webpack', 'web_root');
        $this->assertEquals($actual, new Process('test_engine_path bin/webpack', 'web_root'));

        $_SERVER['PATH'] = $serverPath;
    }
}
