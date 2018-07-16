<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit;

use Oro\Bundle\ImportExportBundle\OroImportExportBundle;

class OroImportExportBundleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OroImportExportBundle
     */
    protected $bundle;

    protected function setUp()
    {
        $this->bundle = new OroImportExportBundle();
    }

    public function testBuild()
    {
        $expectedCompilerPasses = [
            'Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\AddNormalizerCompilerPass',
            'Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ProcessorRegistryCompilerPass',
            'Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\TemplateEntityRepositoryCompilerPass',
            'Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\FormatterProviderPass',
            'Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\WriterCompilerPass',
            'Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ReaderCompilerPass',
            'Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ContextAggregatorCompilerPass',
            'Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ImportExportConfigurationRegistryCompilerPass',
        ];

        $containerBuilderMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(array('addCompilerPass'))
            ->getMock();
        for ($i = 0; $i < count($expectedCompilerPasses); $i++) {
            $containerBuilderMock->expects($this->at($i))
                ->method('addCompilerPass')
                ->with($this->isInstanceOf($expectedCompilerPasses[$i]));
        }

        $this->bundle->build($containerBuilderMock);
    }
}
