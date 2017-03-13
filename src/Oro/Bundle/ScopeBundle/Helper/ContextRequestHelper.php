<?php

namespace Oro\Bundle\ScopeBundle\Helper;

use Symfony\Component\HttpFoundation\Request;

class ContextRequestHelper
{
    /**
     * @param Request $request
     * @param array   $allowedKeys
     * @return array
     */
    public function getFromRequest(Request $request, array $allowedKeys = [])
    {
        $context = (array)$request->query->get('context', []);
        $notAllowedKeys = array_intersect(array_keys($context), array_flip($allowedKeys));
        if (count($notAllowedKeys) || count($context) !== count($allowedKeys)) {
            throw new \RuntimeException(
                sprintf('Context must contain only allowed keys: %s', implode(', ', $allowedKeys))
            );
        }

        return $context;
    }
}
