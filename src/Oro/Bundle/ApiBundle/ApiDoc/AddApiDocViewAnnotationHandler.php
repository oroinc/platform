<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Routing\Route;

/**
 * Adds an additional view to ApiDoc annotation if the annotation contains specific view.
 */
class AddApiDocViewAnnotationHandler implements ApiDocAnnotationHandlerInterface
{
    /** @var string */
    private $additionalView;

    /** @var string */
    private $existingView;

    /**
     * @param string $additionalView
     * @param string $existingView
     */
    public function __construct(string $additionalView, string $existingView)
    {
        $this->additionalView = $additionalView;
        $this->existingView = $existingView;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ApiDoc $annotation, Route $route)
    {
        $views = $annotation->getViews();
        if (!empty($views)
            && in_array($this->existingView, $views, true)
            && !in_array($this->additionalView, $views, true)
        ) {
            $annotation->addView($this->additionalView);
        }
    }
}
