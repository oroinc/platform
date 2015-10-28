<?php

namespace Oro\Bundle\InstallerBundle\Process\Step;

use Doctrine\ORM\EntityManager;

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
                return $this->handleAjaxAction('cache:clear', array('--no-optional-warmers' => true));
            case 'clear-config':
                return $this->handleAjaxAction('oro:entity-config:cache:clear', array('--no-warmup' => true));
            case 'clear-extend':
                return $this->handleAjaxAction('oro:entity-extend:cache:clear', array('--no-warmup' => true));
            case 'schema-drop':
                return $this->handleDropDatabase($context);
            case 'schema-update':
                return $this->handleAjaxAction('oro:migration:load', array('--force' => true));
            case 'fixtures':
                return $this->handleAjaxAction(
                    'oro:migration:data:load',
                    array('--no-interaction' => true)
                );
            case 'workflows':
                return $this->handleAjaxAction('oro:workflow:definitions:load');
            case 'processes':
                return $this->handleAjaxAction('oro:process:configuration:load');
        }

        return $this->render(
            'OroInstallerBundle:Process/Step:schema.html.twig',
            array(
                'dropDatabase' => in_array($context->getStorage()->get('dropDatabase', 'none'), ['app', 'full'], true)
            )
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
                        array('--force' => true, '--em' => $name)
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
                array('--force' => true, '--full-database' => true)
            );
        } else {
            return true;
        }
    }
}
