<?php

namespace Oro\Bundle\SecurityBundle\Util;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Contains handy methods for checking if HTTP referer can be safely used in links.
 */
class SameSiteUrlHelper
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getSameSiteReferer(Request $request = null, string $fallbackUrl = ''): string
    {
        $request = $request ?? $this->requestStack->getMainRequest();
        if (!$request) {
            return $fallbackUrl;
        }

        $referer = (string) $request->headers->get('referer');

        return $this->isSameSiteUrl($referer, $request) ? $referer : $fallbackUrl;
    }

    private function isSameSiteUrl(string $url, ?Request $request = null): bool
    {
        $request = $request ?? $this->requestStack->getMainRequest();
        $refererParts = parse_url($url);
        $isValid = $url !== '';

        if (!empty($refererParts['host']) && $refererParts['host'] !== $request->getHost()) {
            $isValid = false;
        } elseif (!empty($refererParts['port']) && $refererParts['port'] !== $request->getPort()) {
            $isValid = false;
        } elseif (!empty($refererParts['scheme'])) {
            if (!in_array($refererParts['scheme'], ['http', 'https'])) {
                $isValid = false;
            } elseif ($refererParts['scheme'] !== 'https' && $request->isSecure()) {
                // Going out from secure connection to insecure page on same domain is not valid.
                $isValid = false;
            }
        }

        return $isValid;
    }
}
