<?php

namespace Oro\Bundle\NavigationBundle\ContentProvider;

use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;
use Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface;

/**
 * Returns a page title.
 */
class TitleContentProvider implements ContentProviderInterface
{
    /** @var TitleServiceInterface */
    private $titleService;

    public function __construct(TitleServiceInterface $titleService)
    {
        $this->titleService = $titleService;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->titleService->render([], null, null, null, true);
    }
}
