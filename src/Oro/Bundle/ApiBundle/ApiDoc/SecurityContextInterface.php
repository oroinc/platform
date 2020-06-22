<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

/**
 * Represents the security context for API sandbox.
 */
interface SecurityContextInterface
{
    /**
     * Indicates whether there is any token in the security context.
     *
     * @return bool
     */
    public function hasSecurityToken(): bool;

    /**
     * Gets all available organizations for the currently logger in user.
     *
     * @return array [organization identifier => organization name, ...]
     */
    public function getOrganizations(): array;

    /**
     * Gets the identifier of the organization for the currently logger in user.
     *
     * @return string|null
     */
    public function getOrganization(): ?string;

    /**
     * Gets the name of the currently logger in user.
     *
     * @return string|null
     */
    public function getUserName(): ?string;

    /**
     * Gets the API key that is used to identify the origin of an API request.
     *
     * @return string|null
     */
    public function getApiKey(): ?string;

    /**
     * Gets a hint with instructions how to generate API key.
     *
     * @return string|null
     */
    public function getApiKeyGenerationHint(): ?string;

    /**
     * Gets the name of CSRF cookie.
     *
     * @return string|null
     */
    public function getCsrfCookieName(): ?string;

    /**
     * Gets the route name to the switch organization.
     *
     * @return string|null
     */
    public function getSwitchOrganizationRoute(): ?string;

    /**
     * Gets the route name to the sign in form.
     *
     * @return string|null
     */
    public function getLoginRoute(): ?string;

    /**
     * Gets the route name to the sign out the currently logged in user.
     *
     * @return string|null
     */
    public function getLogoutRoute(): ?string;
}
