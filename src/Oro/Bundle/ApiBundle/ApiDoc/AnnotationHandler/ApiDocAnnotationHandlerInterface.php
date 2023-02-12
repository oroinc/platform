<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Routing\Route;

/**
 * Provides an interface for classes that can be used to update ApiDoc annotation
 * before it will be processed by ApiDocExtractor.
 */
interface ApiDocAnnotationHandlerInterface
{
    /**
     * Updates ApiDoc annotation before it will be processed by ApiDocExtractor.
     */
    public function handle(ApiDoc $annotation, Route $route): void;
}
