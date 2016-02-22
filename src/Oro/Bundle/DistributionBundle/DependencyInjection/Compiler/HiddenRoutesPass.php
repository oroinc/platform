<?php

namespace Oro\Bundle\DistributionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class HiddenRoutesPass implements CompilerPassInterface
{
    const MATCHER_DUMPER_CLASS_PARAM    = 'router.options.matcher_dumper_class';
    const EXPECTED_MATCHER_DUMPER_CLASS = 'Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper';
    const NEW_MATCHER_DUMPER_CLASS      = 'Oro\Component\Routing\Matcher\PhpMatcherDumper';

    const API_DOC_EXTRACTOR_CLASS_PARAM            = 'nelmio_api_doc.extractor.api_doc_extractor.class';
    const EXPECTED_API_DOC_EXTRACTOR_CLASS         = 'Nelmio\ApiDocBundle\Extractor\ApiDocExtractor';
    const EXPECTED_CACHING_API_DOC_EXTRACTOR_CLASS = 'Nelmio\ApiDocBundle\Extractor\CachingApiDocExtractor';
    const NEW_API_DOC_EXTRACTOR_CLASS              = 'Oro\Component\Routing\ApiDoc\ApiDocExtractor';
    const NEW_CACHING_API_DOC_EXTRACTOR_CLASS      = 'Oro\Component\Routing\ApiDoc\CachingApiDocExtractor';

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

        if ($container->hasParameter(self::API_DOC_EXTRACTOR_CLASS_PARAM)) {
            $newClass = $this->getNewApiDocExtractorClass(
                $container->getParameter(self::API_DOC_EXTRACTOR_CLASS_PARAM)
            );
            if ($newClass) {
                $container->setParameter(self::API_DOC_EXTRACTOR_CLASS_PARAM, $newClass);
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

    /**
     * @param string $currentClass
     *
     * @return string|null
     */
    protected function getNewApiDocExtractorClass($currentClass)
    {
        switch ($currentClass) {
            case self::EXPECTED_CACHING_API_DOC_EXTRACTOR_CLASS:
                return self::NEW_CACHING_API_DOC_EXTRACTOR_CLASS;
            case self::EXPECTED_API_DOC_EXTRACTOR_CLASS:
                return self::NEW_API_DOC_EXTRACTOR_CLASS;
            default:
                return null;
        }
    }
}
