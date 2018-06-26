<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Oro\Bundle\SearchBundle\OroSearchBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroSearchBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldRegisterExpectedCompilerPasses()
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($this->at(0))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(AddTopicMetaPass::class))
        ;

        $bundle = new OroSearchBundle();
        $bundle->build($container);
    }
}
