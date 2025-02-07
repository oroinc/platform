<?php

namespace Oro\Bundle\ApiBundle\Security\Http\EntryPoint;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Authentication entry point for API security firewalls.
 */
class ApiEntryPoint implements AuthenticationEntryPointInterface
{
    #[\Override]
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new Response('', 401, ['WWW-Authenticate' => 'Basic']);
    }
}
