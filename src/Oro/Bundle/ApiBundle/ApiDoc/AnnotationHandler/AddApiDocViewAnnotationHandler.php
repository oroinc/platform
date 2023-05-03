<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Routing\Route;

/**
 * Adds an additional view to ApiDoc annotation if the annotation contains specific view.
 */
class AddApiDocViewAnnotationHandler implements ApiDocAnnotationHandlerInterface
{
    private string $additionalView;
    private string $existingView;

    public function __construct(string $additionalView, string $existingView)
    {
        $this->additionalView = $additionalView;
        $this->existingView = $existingView;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ApiDoc $annotation, Route $route): void
    {
        $views = $annotation->getViews();
        if (!empty($views)
            && \in_array($this->existingView, $views, true)
            && !\in_array($this->additionalView, $views, true)
        ) {
            $annotation->addView($this->additionalView);
        }
    }
}
