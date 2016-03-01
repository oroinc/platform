<?php

namespace Oro\Bundle\CronBundle\JobQueue;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;

use JMS\JobQueueBundle\DependencyInjection\CompilerPass\LinkGeneratorsPass;
use JMS\JobQueueBundle\DependencyInjection\JMSJobQueueExtension;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * This class was created to avoid performance issue during default JobQueueBundle boot.
 */
class JMSJobQueueBundle extends Bundle
{
    /**
     * Real bundle path
     * @var string
     */
    private $bundleRir;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->bundleRir = realpath($kernel->getRootDir() . '/../vendor/jms/job-queue-bundle/JMS/JobQueueBundle/');
        $this->name = 'JMSJobQueueBundle';
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new LinkGeneratorsPass());
        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createAnnotationMappingDriver(
                ['JMS\JobQueueBundle\Entity'],
                [$this->bundleRir . '/Entity'],
                [],
                false,
                ['JMSJobQueueBundle' => 'JMS\JobQueueBundle\Entity']
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->bundleRir;
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new JMSJobQueueExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return 'JMS\JobQueueBundle';
    }
}
