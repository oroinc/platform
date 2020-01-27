<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit;

use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\AddNormalizerCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ContextAggregatorCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\FormatterProviderPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\IdentityValidationLoaderPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ImportExportConfigurationRegistryCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ProcessorRegistryCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ReaderCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\TemplateEntityRepositoryCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\WriterCompilerPass;
use Oro\Bundle\ImportExportBundle\OroImportExportBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
            IdentityValidationLoaderPass::class,
            AddNormalizerCompilerPass::class,
            ProcessorRegistryCompilerPass::class,
            TemplateEntityRepositoryCompilerPass::class,
            FormatterProviderPass::class,
            WriterCompilerPass::class,
            ReaderCompilerPass::class,
            ContextAggregatorCompilerPass::class,
            ImportExportConfigurationRegistryCompilerPass::class
        ];

        $containerBuilderMock = $this->createMock(ContainerBuilder::class);
        for ($i = 0; $i < count($expectedCompilerPasses); $i++) {
            $containerBuilderMock->expects($this->at($i))
                ->method('addCompilerPass')
                ->with($this->isInstanceOf($expectedCompilerPasses[$i]));
        }

        $this->bundle->build($containerBuilderMock);
    }
}
