<?php

namespace Oro\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

/**
 * Replaces URL schema to pgsql+advisory
 */
class PgsqlAdvisorySchemaInUrlEnvVarProcessor implements EnvVarProcessorInterface
{
    public static function getProvidedTypes()
    {
        return [
            'pgsql_advisory_schema' => 'string',
        ];
    }

    public function getEnv(string $prefix, string $name, \Closure $getEnv)
    {
        $url = $getEnv($name);

        $strpos = strpos($url, "://");
        if (false !== $strpos) {
            $url = substr_replace($url, 'pgsql+advisory', 0, $strpos);
        }

        return $url;
    }
}
