<?php

namespace Oro\Bundle\NavigationBundle\ContentProvider;

use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;
use Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface;

/**
 * Returns serialized title data for a page title.
 */
class TitleSerializedContentProvider implements ContentProviderInterface
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
        return $this->titleService->getSerialized();
    }
}
