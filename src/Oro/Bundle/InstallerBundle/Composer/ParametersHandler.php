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
  <comment>Enable all enterprise services:</comment>
  composer set-parameters \\
  \tdatabase_driver=pdo_pgsql \\
  \tsearch_engine_name=elastic_search \\
  \tmessage_queue_transport=amqp \\
  \tmessage_queue_transport_config="{\\ 
  \t\thost: '%env(ORO_MQ_HOST)%', \\
  \t\tport: '%env(ORO_MQ_PORT)%', \\
  \t\tuser: '%env(ORO_MQ_USER)%', \\
  \t\tpassword: '%env(ORO_MQ_PASSWORD)%', 
  \t\tvhost: '/'}" \\
  \tredis_dsn_cache='%env(ORO_REDIS_URL)%/1' \\
  \tredis_dsn_doctrine='%env(ORO_REDIS_URL)%/2'
  <comment>Mark application as not installed:</comment>
  composer set-parameters installed=null
<info>Database:</info>
<info>---------</info>
  <comment>Postgres:</comment>
  composer set-parameters database_driver=pdo_pgsql
  <comment>MySQL:</comment>
  composer set-parameters database_driver=pdo_mysql
<info>ElasticSearch:</info>
<info>--------------</info>
  <comment>Enable:</comment>
  composer set-parameters search_engine_name=elastic_search
  <comment>Disable:</comment>
  composer set-parameters search_engine_name=orm
<info>Redis:</info>
<info>------</info>
  <comment>Cache:</comment>
  composer set-parameters \\
  \tredis_dsn_cache='%env(ORO_REDIS_URL)%/1' \\
  \tredis_dsn_doctrine='%env(ORO_REDIS_URL)%/2'
  <comment>Sessions:</comment>
  composer set-parameters \\
  \tsession_handler=snc_redis.session.handler\\
  \tredis_dsn_session='%env(ORO_REDIS_URL)%/0'
  <comment>Disable:</comment>
  composer set-parameters session_handler=session.handler.native_file \\
  \tredis_dsn_cache redis_dsn_doctrine redis_dsn_session
<info>RabbitMQ:</info>
<info>---------</info>
  <comment>Enable:</comment>
  composer set-parameters \\
  \tmessage_queue_transport=amqp \\
  \tmessage_queue_transport_config="{\\ 
  \t\thost: '%env(ORO_MQ_HOST)%', \\
  \t\tport: '%env(ORO_MQ_PORT)%', \\
  \t\tuser: '%env(ORO_MQ_USER)%', \\
  \t\tpassword: '%env(ORO_MQ_PASSWORD)%', 
  \t\tvhost: '/'}"
  <comment>Disable:</comment>
  composer set-parameters message_queue_transport=dbal message_queue_transport_config

DOC;

    public static function set(Event $event)
    {
        $options = $event->getComposer()->getPackage()->getExtra();
        $parametersFile = $options['incenteev-parameters']['file'] ?? 'config/parameters.yml';

        if (is_file($parametersFile) && is_writable($parametersFile)) {
            $parameters = Yaml::parse(file_get_contents($parametersFile));
            $arguments = $event->getArguments();
            if (empty($arguments)) {
                $event->getIO()->write(self::DOCUMENTATION);

                return;
            }
            $updatedParameters = ['parameters' => []];
            foreach ($arguments as $parameter) {
                $strpos = strpos($parameter, '=');
                if ($strpos) {
                    $name = substr($parameter, 0, $strpos);
                    $value = substr($parameter, $strpos + 1);
                    if ('%' !== substr($value, 0, 1)) {
                        $value = Yaml::parse($value);
                    }
                } else {
                    $name = $parameter;
                    $value = null;
                }
                $updatedParameters['parameters'][$name] = $value;
            }
            $event->getIO()->write('<info>Updated parameters.yml file:</info>');
            $event->getIO()->write(Yaml::dump($updatedParameters, 99));
            $parameters = array_replace_recursive($parameters, $updatedParameters);
            file_put_contents($parametersFile, Yaml::dump($parameters, 99));
        } else {
            $event->getIO()->write(
                [
                    '<comment>Cannot patch parameters.yml because file does not exist or not writable</comment>',
                    '<comment>Please run</comment> <error>composer build-parameters</error> <comment>first</comment>',
                ]
            );
        }

        return;
    }
}
