<?php

namespace Oro\Bundle\InstallerBundle\Process\Step;

use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;

use Symfony\Component\HttpFoundation\JsonResponse;

use Oro\Bundle\InstallerBundle\InstallerEvents;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Bundle\InstallerBundle\ScriptExecutor;

class InstallationStep extends AbstractStep
{
    public function displayAction(ProcessContextInterface $context)
    {
        set_time_limit(900);

        $action = $this->getRequest()->query->get('action');
        switch ($action) {
            case 'fixtures':
                return $this->handleAjaxAction('oro:installer:fixtures:load', array('--fixtures-type' => 'demo'));
            case 'navigation':
                return $this->handleAjaxAction('oro:navigation:init');
            case 'js-routing':
                return $this->handleAjaxAction('fos:js-routing:dump', array('--target' => 'js/routes.js'));
            case 'localization':
                return $this->handleAjaxAction('oro:localization:dump');
            case 'assets':
                return $this->handleAjaxAction('assets:install', array('target' => './'));
            case 'assetic':
                return $this->handleAjaxAction('assetic:dump');
            case 'translation':
                return $this->handleAjaxAction('oro:translation:dump');
            case 'requirejs':
                return $this->handleAjaxAction('oro:requirejs:build', array('--ignore-errors' => true));
            case 'finish':
                $this->get('event_dispatcher')->dispatch(InstallerEvents::FINISH);
                // everything was fine - update installed flag in parameters.yml
                $dumper = $this->get('oro_installer.yaml_persister');
                $params = $dumper->parse();
                $params['system']['installed'] = date('c');
                $dumper->dump($params);
                // launch 'cache:clear' to set installed flag in DI container
                // suppress warning: ini_set(): A session is active. You cannot change the session
                // module's ini settings at this time
                error_reporting(E_ALL ^ E_WARNING);
                return $this->handleAjaxAction('cache:clear');
        }

        // check if we have package installation step
        if (strpos($action, 'installerScript-') !== false) {
            $scriptFile = $this->container->get('oro_installer.script_manager')->getScriptFileByKey(
                str_replace('installerScript-', '', $action)
            );

            $scriptExecutor = new ScriptExecutor(
                $this->getOutput(),
                $this->container,
                new CommandExecutor(
                    $this->container->getParameter('kernel.environment'),
                    $this->getOutput(),
                    $this->getApplication()
                )
            );
            $scriptExecutor->runScript($scriptFile);

            return new JsonResponse(array('result' => true));
        }

        return $this->render(
            'OroInstallerBundle:Process/Step:installation.html.twig',
            array(
                'loadFixtures' => $context->getStorage()->get('loadFixtures'),
                'installerScripts' => $this
                        ->container
                        ->get('oro_installer.script_manager')
                        ->getScriptLabels(),
            )
        );
    }
}
