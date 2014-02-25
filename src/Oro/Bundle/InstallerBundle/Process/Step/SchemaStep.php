<?php

namespace Oro\Bundle\InstallerBundle\Process\Step;

use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;

class SchemaStep extends AbstractStep
{
    public function displayAction(ProcessContextInterface $context)
    {
        set_time_limit(600);

        switch ($this->getRequest()->query->get('action')) {
            case 'cache':
                // suppress warning: ini_set(): A session is active. You cannot change the session
                // module's ini settings at this time
                error_reporting(E_ALL ^ E_WARNING);
                return $this->handleAjaxAction('cache:clear');
            case 'clear':
                return $this->handleAjaxAction('oro:entity-extend:clear');
            case 'schema-drop':
                return $this->handleAjaxAction(
                    'doctrine:schema:drop',
                    array('--force' => true, '--full-database' => true)
                );
            case 'schema-create':
                return $this->handleAjaxAction('doctrine:schema:create');
            case 'init-config':
                return $this->handleAjaxAction('oro:entity-config:init');
            case 'init-extend':
                return $this->handleAjaxAction('oro:entity-extend:init');
            case 'update-config':
                return $this->handleAjaxAction('oro:entity-extend:update-config');
            case 'schema-update':
                return $this->handleAjaxAction('oro:installer:migration:load');
            case 'fixtures':
                return $this->handleAjaxAction(
                    'oro:installer:fixtures:load',
                    array('--no-interaction' => true)
                );
            case 'workflows':
                return $this->handleAjaxAction('oro:workflow:definitions:load');
        }

        return $this->render('OroInstallerBundle:Process/Step:schema.html.twig');
    }
}
