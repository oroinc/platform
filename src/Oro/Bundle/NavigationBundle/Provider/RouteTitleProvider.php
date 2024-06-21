<?php

declare(strict_types=1);

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\NavigationBundle\Title\TitleReader\TitleReaderRegistry;

/**
 * Provides a title for a given route.
 */
class RouteTitleProvider
{
    public function __construct(
        private readonly TitleReaderRegistry $readerRegistry,
        private readonly TitleTranslator $titleTranslator,
        private readonly TitleServiceInterface $titleService,
    ) {
    }

    /**
     * Provides a title taken from then the specified menu $menuName for given route $routeName.
     *
     * @param string $routeName
     * @param string $menuName
     *
     * @return string
     */
    public function getTitle(string $routeName, string $menuName): string
    {
        $title = $this->readerRegistry->getTitleByRoute($routeName);
        if ($title) {
            return $this->titleTranslator
                ->trans($this->titleService->createTitle($routeName, $title, $menuName));
        }

        return '';
    }
}
