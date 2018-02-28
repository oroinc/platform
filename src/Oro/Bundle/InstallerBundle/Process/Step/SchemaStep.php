<?php

namespace Oro\Bundle\InstallerBundle\Process\Step;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\InstallerBundle\InstallerEvents;
use Oro\Bundle\SecurityBundle\Command\LoadPermissionConfigurationCommand;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;

/**
 * Database preparation step of web installer
 */
class SchemaStep extends AbstractStep
{
    /**
     * @param ProcessContextInterface $context
     * @return mixed
     */
    public function displayAction(ProcessContextInterface $context)
    {
        set_time_limit(600);

        switch ($this->get('request_stack')->getCurrentRequest()->query->get('action')) {
            case 'cache':
                // suppress warning: ini_set(): A session is active. You cannot change the session
                // module's ini settings at this time
                error_reporting(E_ALL ^ E_WARNING);
                return $this->handleAjaxAction('cache:clear', ['--no-optional-warmers' => true]);
            case 'clear-config':
                return $this->handleAjaxAction('oro:entity-config:cache:clear', ['--no-warmup' => true]);
            case 'clear-extend':
                return $this->handleAjaxAction('oro:entity-extend:cache:clear', ['--no-warmup' => true]);
            case 'schema-drop':
                return $this->handleDropDatabase($context);
            case 'before-database':
                return $this->handleAjaxAction(
                    self::TRIGGER_EVENT,
                    ['name' => InstallerEvents::INSTALLER_BEFORE_DATABASE_PREPARATION]
                );
            case 'schema-update':
                return $this->handleAjaxAction('oro:migration:load', ['--force' => true]);
            case 'fixtures':
                return $this->handleAjaxAction('oro:migration:data:load', ['--no-interaction' => true]);
            case 'permissions':
                return $this->handleAjaxAction(LoadPermissionConfigurationCommand::NAME);
            case 'workflows':
                return $this->handleAjaxAction('oro:workflow:definitions:load');
            case 'crons':
                return $this->handleAjaxAction('oro:cron:definitions:load');
            case 'processes':
                return $this->handleAjaxAction('oro:process:configuration:load');
        }

        return $this->render(
            'OroInstallerBundle:Process/Step:schema.html.twig',
            [
                'dropDatabase' => in_array($context->getStorage()->get('dropDatabase', 'none'), ['app', 'full'], true)
            ]
        );
    }

    /**
     * @param ProcessContextInterface $context
     *
     * @return mixed
     */
    protected function handleDropDatabase(ProcessContextInterface $context)
    {
        $dropDatabase = $context->getStorage()->get('dropDatabase', 'none');
        if ($dropDatabase === 'app') {
            $exitCode = 0;
            $managers = $this->get('doctrine')->getManagers();
            foreach ($managers as $name => $manager) {
                if ($manager instanceof EntityManager) {
                    $exitCode = $this->runCommand(
                        'doctrine:schema:drop',
                        ['--force' => true, '--em' => $name]
                    );
                    if ($exitCode) {
                        break;
                    }
                }
            }

            return $this->getAjaxActionResponse($exitCode);
        } elseif ($dropDatabase === 'full') {
            return $this->handleAjaxAction(
                'doctrine:schema:drop',
                ['--force' => true, '--full-database' => true]
            );
        } else {
            return true;
        }
    }
}
