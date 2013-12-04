<?php

namespace Oro\Bundle\DistributionBundle\Controller;

use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class PackageController extends Controller
{
    const CODE_INSTALLED = 0;
    const CODE_UPDATED = 0;
    const CODE_UNINSTALLED = 0;
    const CODE_ERROR = 1;
    const CODE_CONFIRM = 2;

    protected function setUpEnvironment()
    {
        $kernelRootDir = $this->container->getParameter('kernel.root_dir');
        putenv(sprintf('COMPOSER_HOME=%s/cache/composer', $kernelRootDir));
        chdir(realpath($kernelRootDir . '/../'));
    }

    /**
     * @Route("/packages/installed")
     * @Template("OroDistributionBundle:Package:list_installed.html.twig")
     */
    public function listInstalledAction()
    {
        $this->setUpEnvironment();

        return ['packages' => $this->container->get('oro_distribution.package_manager')->getInstalled()];
    }

    /**
     * @Route("/packages/available")
     * @Template("OroDistributionBundle:Package:list_available.html.twig")
     */
    public function listAvailableAction()
    {
        $this->setUpEnvironment();

        return ['packages' => $this->container->get('oro_distribution.package_manager')->getAvailable()];
    }

    /**
     * @Route("/packages/updates")
     * @Template("OroDistributionBundle:Package:list_updates.html.twig")
     */
    public function listUpdatesAction()
    {
        $this->setUpEnvironment();

        return ['updates' => $this->container->get('oro_distribution.package_manager')->getAvailableUpdates()];
    }

    /**
     * @Route("/packages/ajax-test")
     */
    public function ajaxTestAction()
    {
        $request = $this->getRequest();
        $text = $request->get('text');
        $response = new Response(json_encode(array('text' => $text)));
        $response->headers->set('Content-Type', 'application/json');
        sleep(3);
        return $response;
    }

    /**
     * @Route("/package/uninstall")
     */
    public function uninstallAction()
    {
        $this->setUpEnvironment();

//        $responseContent = [
//            'code' => self::CODE_ERROR,
//            'message' => 'Not implemented yet'
//        ];
//        $response = new Response();
//        $response->headers->set('Content-Type', 'application/json');
//        $response->setContent(json_encode($responseContent));
//
//        return $response;

        $params = $this->getRequest()->get('params');
        $packageName = $params['packageName'];
        $forceDependentsUninstalling = isset($params['force']) ? (bool)$params['force'] : false;

        /** @var PackageManager $manager */
        $manager = $this->container->get('oro_distribution.package_manager');
        $responseContent = [];
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        if (!$manager->isPackageInstalled($packageName)) {
            $responseContent = [
                'code' => self::CODE_ERROR,
                'message' => sprintf('Package % not found', $packageName)
            ];
            $response->setContent(json_encode($responseContent));

            return $response;
        }

        $dependents = $manager->getDependents($packageName);
        if (!$forceDependentsUninstalling && $dependents) {
            $params['force'] = true;
            $responseContent = [
                'code' => self::CODE_CONFIRM,
                'packages' => $dependents,
                'params' => $params
            ];
            $response->setContent(json_encode($responseContent));

            return $response;

        }

        $manager->uninstall(array_merge($dependents, [$packageName]));
        $responseContent = ['code' => self::CODE_UNINSTALLED];
        $response->setContent(json_encode($responseContent));

        return $response;
    }

    /**
     * @Route("/package/install")
     */
    public function installAction()
    {
        $this->setUpEnvironment();

        $params = $this->getRequest()->get('params');
        $packageName = $params['packageName'];
        $packageVersion = $params['packageVersion'];
        $forceDependenciesInstalling = isset($params['force']) ? (bool)$params['force'] : false;

        /** @var PackageManager $manager */
        $manager = $this->container->get('oro_distribution.package_manager');
        $responseContent = [
            'code' => self::CODE_ERROR,
            'message' => 'Not implemented yet'
        ];
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($responseContent));

        return $response;
    }

    /**
     * @Route("/package/update")
     */
    public function updateAction()
    {
        $this->setUpEnvironment();

        $params = $this->getRequest()->get('params');
        $packageName = $params['packageName'];

        /** @var PackageManager $manager */
        $manager = $this->container->get('oro_distribution.package_manager');
        $responseContent = [
            'code' => self::CODE_ERROR,
            'message' => 'Not implemented yet'
        ];
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($responseContent));

        return $response;
    }


}
