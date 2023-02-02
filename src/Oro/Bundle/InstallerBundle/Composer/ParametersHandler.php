<?php

namespace Oro\Bundle\InstallerBundle\Composer;

use Composer\Script\Event;
use Symfony\Component\Yaml\Yaml;

/**
 * Script handler for composer to update parameters.yml file from CLI
 */
class ParametersHandler
{
    const DOCUMENTATION = <<<DOC

<info>Usage examples:</info>
<info>===============</info>

  <comment>Enable MongoDB and Redis:</comment>
  <comment>-------------------------</comment>
  composer set-parameters mongo redis
  <comment>Enable Redis:</comment>
  <comment>-------------</comment>
  composer set-parameters redis
  <comment>Enable MongoDB:</comment>
  <comment>--------------</comment>
  composer set-parameters mongo

DOC;

    public static function set(Event $event)
    {
        $arguments = $event->getArguments();
        if (empty($arguments)) {
            $event->getIO()->write(self::DOCUMENTATION);

            return;
        }
        $parametersFile = 'config/parameters.yml';
        if (!is_file($parametersFile)) {
            file_put_contents($parametersFile, '');
        }

        if (is_file($parametersFile) && is_writable($parametersFile)) {
            $parameters = Yaml::parse(file_get_contents($parametersFile));
            if (!$parameters) {
                $parameters = ['parameters' => []];
            }
            $updatedParameters = self::getUpdatedParameters($arguments);
            $event->getIO()->write('<info>Updated parameters.yml file:</info>');
            $event->getIO()->write(Yaml::dump($updatedParameters, 99));
            $parameters = array_replace_recursive($parameters, $updatedParameters);
            file_put_contents($parametersFile, Yaml::dump($parameters, 99));
        } else {
            $event->getIO()->write(
                [
                    '<comment>Cannot patch parameters.yml because file is not writable</comment>',
                ]
            );
        }
    }

    private static function getUpdatedParameters(array $arguments): array
    {
        $updatedParameters = ['parameters' => []];
        foreach ($arguments as $parameter) {
            if ($parameter === 'mongo') {
                $updatedParameters['parameters']['gaufrette_adapter.public'] =
                    'gridfs:%env(ORO_MONGODB_DSN_PUBLIC)%';
                $updatedParameters['parameters']['gaufrette_adapter.private'] =
                    'gridfs:%env(ORO_MONGODB_DSN_PRIVATE)%';
                continue;
            }
            if ($parameter === 'redis') {
                $updatedParameters['parameters']['redis_dsn_cache'] = '%env(ORO_REDIS_CACHE_DSN)%';
                $updatedParameters['parameters']['redis_dsn_doctrine'] = '%env(ORO_REDIS_DOCTRINE_DSN)%';
                $updatedParameters['parameters']['redis_dsn_layout'] = '%env(ORO_REDIS_LAYOUT_DSN)%';
                continue;
            }

            $strpos = strpos($parameter, '=');
            if ($strpos) {
                $name = substr($parameter, 0, $strpos);
                $value = substr($parameter, $strpos + 1);
                if (!str_starts_with($value, '%')) {
                    $value = Yaml::parse($value);
                }
            } else {
                $name = $parameter;
                $value = null;
            }
            $updatedParameters['parameters'][$name] = $value;
        }

        return $updatedParameters;
    }
}
