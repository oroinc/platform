<?php

namespace Oro\Bundle\NavigationBundle\ContentProvider;

use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;
use Oro\Bundle\UIBundle\ContentProvider\AbstractContentProvider;

abstract class TitleServiceAwareContentProvider extends AbstractContentProvider
{
    /**
     * @var TitleServiceInterface
     */
    protected $titleService;

    /**
     * @param TitleServiceInterface $titleService
     */
    public function __construct(TitleServiceInterface $titleService)
    {
        $this->titleService = $titleService;
    }
}
