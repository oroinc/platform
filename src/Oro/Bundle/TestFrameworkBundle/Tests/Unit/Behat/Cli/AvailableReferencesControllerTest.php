<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Cli;

use Oro\Bundle\TestFrameworkBundle\Behat\Cli\AvailableReferencesController;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\OroAliceLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\DoctrineIsolator;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub\KernelStub;
use Oro\Component\Testing\TempDirExtension;
use Oro\Component\Testing\Unit\Command\Stub\InputStub;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use Symfony\Component\Console\Command\Command;

class AvailableReferencesControllerTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    public function testConfigure()
    {
        $aliceLoader = new OroAliceLoader();
        $doctrineIsolator = $this->createMock(DoctrineIsolator::class);
        $kernel = new KernelStub($this->getTempDir('test_kernel_logs'));
        $controller = new AvailableReferencesController($aliceLoader, $doctrineIsolator, $kernel);

        $command = new Command('test');
        $controller->configure($command);

        $this->assertTrue($command->getDefinition()->hasOption('available-references'));
        $this->assertFalse($command->getDefinition()->getOption('available-references')->isValueRequired());
        $this->assertEmpty($command->getDefinition()->getOption('available-references')->getDefault());
    }

    public function testExecute()
    {
        $aliceLoader = new OroAliceLoader();
        $doctrineIsolator = $this->createMock(DoctrineIsolator::class);
        $doctrineIsolator->expects($this->once())->method('initReferences');
        $kernel = new KernelStub($this->getTempDir('test_kernel_logs'));
        $controller = new AvailableReferencesController($aliceLoader, $doctrineIsolator, $kernel);
        $output = new OutputStub();
        $returnCode = $controller->execute(new InputStub('', [], ['available-references' => true]), $output);
        $this->assertSame(0, $returnCode);
    }

    public function testNotExecute()
    {
        $aliceLoader = new OroAliceLoader();
        $doctrineIsolator = $this->createMock(DoctrineIsolator::class);
        $doctrineIsolator->expects($this->never())->method('initReferences');
        $kernel = new KernelStub($this->getTempDir('test_kernel_logs'));
        $controller = new AvailableReferencesController($aliceLoader, $doctrineIsolator, $kernel);
        $output = new OutputStub();
        $returnCode = $controller->execute(new InputStub(), $output);
        $this->assertNotSame(0, $returnCode);
    }
}
