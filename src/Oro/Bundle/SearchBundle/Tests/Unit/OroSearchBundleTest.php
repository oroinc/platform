<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Oro\Bundle\SearchBundle\OroSearchBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroSearchBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldRegisterExpectedCompilerPasses()
    {
        $container = $this->getMock(ContainerBuilder::class);
        $container
            ->expects($this->at(0))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(AddTopicMetaPass::class))
        ;

        $bundle = new OroSearchBundle();
        $bundle->build($container);
    }
}
