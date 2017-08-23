<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

use Behat\Behat\Context\ContextClass\ClassResolver;
use Symfony\Component\HttpKernel\KernelInterface;

final class OroBehatClassResolver implements ClassResolver
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($contextString)
    {
        return preg_match('/^[a-zA-Z]+::[a-zA-Z]+$/', $contextString);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveClass($contextClassShortcut)
    {
        list($bundleName, $contextClass) = explode('::', $contextClassShortcut);
        $fqdn = $this->kernel->getBundle('!' . $bundleName)->getNamespace() . '\Tests\Behat\Context\\' . $contextClass;

        if (!class_exists($fqdn)) {
            throw new \RuntimeException(
                sprintf('Context shirtcut "%s" was parsed to not existent "%s"', $contextClassShortcut, $fqdn)
            );
        }

        return $fqdn;
    }
}
