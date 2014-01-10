<?php

namespace Oro\Bundle\DistributionBundle\Controller;

use Oro\Bundle\DistributionBundle\Entity\PackageRequirement;
use Oro\Bundle\DistributionBundle\Exception\VerboseException;
use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
        $manager = $this->getPackageManager();
        $items = [];

        foreach ($manager->getInstalled() as $package) {
            $items[] = [
                'package' => $package,
                'update' => $manager->getPackageUpdate($package),
                'canBeDeleted' => $manager->canBeDeleted($package->getPrettyName())
            ];
        }

        return ['items' => $items];
    }

    /**
     * @Route("/packages/available")
     * @Template("OroDistributionBundle:Package:list_available.html.twig")
     */
    public function listAvailableAction()
    {
        $this->setUpEnvironment();
        $packageManager = $this->getPackageManager();

        return ['packages' => $packageManager->getAvailable()];
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
     * @Route("/package/uninstall")
     */
    public function uninstallAction()
    {
        $this->setUpEnvironment();

        $params = $this->getRequest()->get('params');
        $packageName = $this->getParamValue($params, 'packageName', null);
        $forceDependentsUninstalling = $this->getParamValue($params, 'force', false);

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
        $packageName = $this->getParamValue($params, 'packageName', null);
        $packageVersion = $this->getParamValue($params, 'version', null);
        $loadDemoData = $this->getParamValue($params, 'loadDemoData', null);
        $forceDependenciesInstalling = $this->getParamValue($params, 'force', false);

        $isConfirmationRequired = ($loadDemoData === null);

        /** @var PackageManager $manager */
        $manager = $this->container->get('oro_distribution.package_manager');
        $responseContent = [];
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        if ($manager->isPackageInstalled($packageName)) {
            $responseContent = [
                'code' => self::CODE_ERROR,
                'message' => 'Package has already been installed'
            ];
            $response->setContent(json_encode($responseContent));

            return $response;
        }


        try {
            if ($isConfirmationRequired) {
                $params['force'] = true;
                $responseContent = [
                    'code' => self::CODE_CONFIRM,
                    'params' => $params
                ];

                if (!$forceDependenciesInstalling && $requirements = $manager->getRequirements($packageName)) {

                    $responseContent['requirements'] = array_map(
                        function (PackageRequirement $pr) {
                            return $pr->toArray();
                        },
                        $requirements
                    );
                }

                $response->setContent(json_encode($responseContent));

                return $response;

            }

            $manager->install($packageName, $packageVersion, (bool)$loadDemoData);

        } catch (\Exception $e) {
            $message = $e instanceof VerboseException ?
                $e->getMessage() . '_' . $e->getVerboseMessage() :
                $e->getMessage();
            $responseContent = [
                'code' => self::CODE_ERROR,
                'message' => $message
            ];
            $response->setContent(json_encode($responseContent));

            return $response;
        }

        $responseContent = ['code' => self::CODE_INSTALLED];
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
        $packageName = $this->getParamValue($params, 'packageName', null);

        /** @var PackageManager $manager */
        $manager = $this->container->get('oro_distribution.package_manager');
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        if (!$manager->isPackageInstalled($packageName)) {
            $responseContent = [
                'code' => self::CODE_ERROR,
                'message' => sprintf('Package %s is not yet installed', $packageName)
            ];
            $response->setContent(json_encode($responseContent));

            return $response;
        }

        if (!$manager->isUpdateAvailable($packageName)) {
            $responseContent = [
                'code' => self::CODE_ERROR,
                'message' => sprintf('No updates available for package %s', $packageName)
            ];
            $response->setContent(json_encode($responseContent));

            return $response;
        }

        try {
            $manager->update($packageName);
        } catch (VerboseException $e) {
            $responseContent = [
                'code' => self::CODE_ERROR,
                'message' => $e->getMessage() . '_' . $e->getVerboseMessage()
            ];
            $response->setContent(json_encode($responseContent));

            return $response;
        } catch (\Exception $e) {
            $responseContent = [
                'code' => self::CODE_ERROR,
                'message' => $e->getMessage()
            ];
            $response->setContent(json_encode($responseContent));

            return $response;
        }
        $responseContent = [
            'code' => self::CODE_UPDATED,
        ];
        $response->setContent(json_encode($responseContent));

        return $response;
    }

    /**
     * @return PackageManager
     */
    protected function getPackageManager()
    {
        return $this->container->get('oro_distribution.package_manager');
    }

    /**
     * @param array $params
     * @param string $paramName
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    protected function getParamValue(array $params, $paramName, $defaultValue)
    {
        return isset($params[$paramName]) ? $params[$paramName] : $defaultValue;
    }
}
