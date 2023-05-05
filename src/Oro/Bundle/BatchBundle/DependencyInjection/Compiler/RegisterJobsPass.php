<?php

namespace Oro\Bundle\BatchBundle\DependencyInjection\Compiler;

use Oro\Bundle\BatchBundle\DependencyInjection\BatchJobsConfiguration;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser as YamlParser;

/**
 * Read the batch_jobs.yml file of the connectors to register the jobs
 */
class RegisterJobsPass implements CompilerPassInterface
{
    private ?NodeInterface $jobsConfig = null;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $registry = $container->getDefinition('oro_batch.connector.registry');

        foreach ($container->getParameter('kernel.bundles') as $bundle) {
            $reflClass = new \ReflectionClass($bundle);
            if (false === $bundleDir = dirname($reflClass->getFileName())) {
                continue;
            }

            if (is_file($configFile = $bundleDir . '/Resources/config/batch_jobs.yml')) {
                $container->addResource(new FileResource($configFile));
                $this->registerJobs($registry, $configFile);
            }
        }

        // Temporary solution, should be refactored loaded in BAP-21714
        $path = $container->getParameter('kernel.project_dir') . '/config/batch_jobs';
        if (is_dir($path)) {
            $finder = new Finder();
            $finder->in($path)->files()->name('*.yml')->name('*.yaml');
            foreach ($finder as $item) {
                $filePath = $item->getRealPath();
                $container->addResource(new FileResource($filePath));
                $this->registerJobs($registry, $filePath);
            }
        }
    }

    private function registerJobs(Definition $definition, string $configFile): void
    {
        $yamlParser = new YamlParser();
        $config = $this->processConfig($yamlParser->parse(file_get_contents($configFile)));

        foreach ($config['jobs'] as $alias => $job) {
            foreach ($job['steps'] as $step) {
                $services = [];
                foreach ($step['services'] as $setter => $serviceId) {
                    $services[$setter] = new Reference($serviceId);
                }

                $parameters = [];
                foreach ($step['parameters'] as $setter => $value) {
                    $parameters[$setter] = $value;
                }

                $definition->addMethodCall(
                    'addStepToJob',
                    [
                        $config['name'],
                        $job['type'],
                        $alias,
                        $job['title'],
                        $step['title'],
                        $step['class'],
                        $services,
                        $parameters,
                    ]
                );
            }
        }
    }

    private function processConfig(array $config): array
    {
        $processor = new Processor();
        if (!$this->jobsConfig) {
            $this->jobsConfig = (new BatchJobsConfiguration())->getConfigTreeBuilder()->buildTree();
        }

        return $processor->process($this->jobsConfig, $config);
    }
}
