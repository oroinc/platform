<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

/**
 * Represents the security context for API sandbox.
 */
interface SecurityContextInterface
{
    /**
     * Indicates whether there is any token in the security context.
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
     */
    public function getOrganization(): ?string;

    /**
     * Gets the name of the currently logger in user.
     */
    public function getUserName(): ?string;

    /**
     * Gets the API key that is used to identify the origin of an API request.
     */
    public function getApiKey(): ?string;

    /**
     * Gets a hint with instructions how to generate API key.
     */
    public function getApiKeyGenerationHint(): ?string;

    /**
     * Gets the name of CSRF cookie.
     */
    public function getCsrfCookieName(): ?string;

    /**
     * Gets the route name to the switch organization.
     */
    public function getSwitchOrganizationRoute(): ?string;

    /**
     * Gets the route name to the sign in form.
     */
    public function getLoginRoute(): ?string;

    /**
     * Gets the route name to the sign out the currently logged in user.
     */
    public function getLogoutRoute(): ?string;
}
