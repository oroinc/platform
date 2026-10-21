<?php

declare(strict_types=1);

namespace Oro\Bundle\UIBundle\Tools;

/**
 * Represents a service that builds absolute and relative URLs and paths and detects local URLs.
 */
interface UrlHelperInterface
{
    /**
     * Returns the absolute URL for the given path, resolved against the current request or the request context.
     */
    public function getAbsoluteUrl(string $path): string;

    /**
     * Returns the given path relative to the path of the current request.
     */
    public function getRelativePath(string $path): string;

    /**
     * Adds base url to the given path if the path is not an absolute URL or does not start with base url.
     */
    public function getAbsolutePath(string $path): string;

    /**
     * Checks if the given URL is a local URL.
     *
     * A URL is considered local if its host matches one of the predefined local hostnames
     * such as 'localhost', '127.0.0.1', or other common local addresses.
     * If the URL is null, the current request's URL is used.
     */
    public function isLocalUrl(?string $url = null): bool;
}
