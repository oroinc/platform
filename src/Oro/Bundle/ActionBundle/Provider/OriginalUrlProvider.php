<?php

namespace Oro\Bundle\ActionBundle\Provider;

use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\DataGridBundle\Converter\UrlConverter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * Provides original URL for action button extensions,
 * basically original URL refers to the master request URL, but
 * in case when master request was ajax request provider tries to
 * build URL using url parameter "originalRoute" (it uses for datagrids)
 */
class OriginalUrlProvider
{
    /** @var RequestStack */
    private $requestStack;

    /** @var RouterInterface */
    private $router;

    /** @var UrlConverter */
    private $datagridUrlConverter;

    public function __construct(
        RequestStack $requestStack,
        RouterInterface $router,
        UrlConverter $datagridUrlConverter
    ) {
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->datagridUrlConverter = $datagridUrlConverter;
    }

    /**
     * This link uses for returning to the previous page in the case
     * when action forwards us to a page that doesn't have any relation with the previous page.
     * We can't use js for this, because it doesn't cover the situation
     * when one user shares this link to another user.
     */
    public function getOriginalUrl(ButtonSearchContext $buttonSearchContext = null): ?string
    {
        $originalUrl = $this->getMasterRequestUri();
        if (null === $originalUrl) {
            return null;
        }

        if (null === $buttonSearchContext) {
            return $originalUrl;
        }

        $datagridName = $buttonSearchContext->getDatagrid();
        if (!$datagridName) {
            return $originalUrl;
        }

        /**
         * We got grid ajax url instead of page when do filtering or another work
         * on the grid that forces to reload grid with ajax. For this case we must
         * rewrite this url on url that will goes to the page where this grid locates.
         */
        return $this->datagridUrlConverter->convertGridUrlToPageUrl($datagridName, $originalUrl);
    }

    private function getMasterRequestUri(): ?string
    {
        $request = $this->requestStack->getMainRequest();

        /**
         * Based on the "requestStack" contract "MasterRequest" could be "null". it could happens in case
         * if this service is called during the console command work.
         *
         * Example of such case is the building of the MarketingList datasource, where datagrid used as a source of
         * the data.
         */
        if (!$request) {
            return null;
        }

        return $request->getRequestUri();
    }
}
