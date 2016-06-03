<?php

namespace Oro\Bundle\DistributionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class HiddenRoutesPass implements CompilerPassInterface
{
    const MATCHER_DUMPER_CLASS_PARAM    = 'router.options.matcher_dumper_class';
    const EXPECTED_MATCHER_DUMPER_CLASS = 'Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper';
    const NEW_MATCHER_DUMPER_CLASS      = 'Oro\Component\Routing\Matcher\PhpMatcherDumper';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasParameter(self::MATCHER_DUMPER_CLASS_PARAM)) {
            $newClass = $this->getNewRoutingMatcherDumperClass(
                $container->getParameter(self::MATCHER_DUMPER_CLASS_PARAM)
            );
            if ($newClass) {
                $container->setParameter(self::MATCHER_DUMPER_CLASS_PARAM, $newClass);
            }
        }
    }

    /**
     * @param string $currentClass
     *
     * @return string|null
     */
    protected function getNewRoutingMatcherDumperClass($currentClass)
    {
        return self::EXPECTED_MATCHER_DUMPER_CLASS === $currentClass
            ? self::NEW_MATCHER_DUMPER_CLASS
            : null;
    }
}
