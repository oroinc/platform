<?php

namespace Oro\Bundle\InstallerBundle;

use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;

class InstallerProvider
{
    const ORO_INSTALLER_STRING = 'OroInstaller';
    const ORO_INSTALLER_FILE_NAME = 'install.php';

    /**
     * @var Kernel
     */
    protected $kernel;

    /**
     * @var array
     */
    protected $installers;

    /**
     * @param $bundles
     * @param $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Run package installer by installer key
     *
     * @param string $installerKey
     * @param StreamOutput $output
     * @param ContainerInterface $containerInstance
     */
    public function runPackageInstaller($installerKey, StreamOutput $output, ContainerInterface $containerInstance)
    {
        $installer = $this->getInstallerByKey($installerKey);
        if ($installer) {
            $output->writeln('');
            $output->writeln(sprintf('[%s] Launching "%s" package installer', date('Y-m-d H:i:s'), $installer['file']));

            if (is_file($installer['file'])) {
                ob_start();
                $container = $containerInstance;
                include($installer['file']);
                $installerOutput = ob_get_contents();
                ob_clean();
                $output->writeln($installerOutput);
            } else {
                $output->writeln('File "%s" not found', $installer['file']);
            }
        } else {
            $output->writeln(sprintf('Installer "%s" was not found', $installerKey));
        }
    }

    /**
     * get array with installers keys and labels
     *
     * @return array
     *  key -> installer file md5 key
     *  value -> installer label
     */
    public function getInstallersLabels()
    {
        $installers = [];
        $this->checkInstallersLoaded();

        if (!empty($this->installers)) {
            foreach ($this->installers as $installer) {
                $installers[$installer['key']] = $installer['label'];
            }
        }

        return $installers;
    }

    /**
     * Get installer info array by installer md5 key
     *
     * @param $installerKey
     * @return array|bool
     */
    public function getInstallerByKey($installerKey)
    {
        $this->checkInstallersLoaded();

        if (!empty($this->installers) && isset($this->installers[$installerKey])) {
            return $this->installers[$installerKey];
        }

        return false;
    }

    /**
     * Get list of installers
     *
     * @return array
     *  key -> installer md5 key
     *  value -> installer info array
     */
    public function getInstallerList()
    {
        $this->checkInstallersLoaded();

        return $this->installers;
    }

    /**
     * Checks if installer was loaded and load they
     */
    protected function checkInstallersLoaded()
    {
        if (!is_array($this->installers)) {
            $this->processInstallers();
        }
    }

    /**
     * Read installers info from installer files
     */
    protected function processInstallers()
    {
        $index = 0;
        $installers = [];
        $this->installers = [];
        $rootDir = realpath($this->kernel->getRootDir() . DIRECTORY_SEPARATOR.'..' . DIRECTORY_SEPARATOR);
        $bundles = $this->kernel->getBundles();
        foreach ($bundles as $bundle) {
            $bundleDirName = $bundle->getPath();
            $this->getInstallerInfo($bundleDirName, $index, $installers);

            $relativePathArray = explode(DIRECTORY_SEPARATOR, str_replace($rootDir, '', $bundleDirName));
            if ($relativePathArray[0] == '') {
                unset ($relativePathArray[0]);
            }
            for ($i = count($relativePathArray); $i >= 0; $i--) {
                unset($relativePathArray[$i]);
                $checkPath = $rootDir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $relativePathArray);
                if ($this->getInstallerInfo($checkPath, $index, $installers)) {
                    break;
                }
            }
        }
        if (!empty($installers)) {
            usort($installers, array($this, "compareInstallers"));
            foreach ($installers as $installer) {
                $this->installers[$installer['key']] = $installer;
            }
        }
    }

    /**
     * Get installer info from dir
     *
     * @param string $dirName
     * @param int $index
     * @param array $installers
     * @return array|bool
     */
    protected function getInstallerInfo($dirName, &$index, &$installers)
    {
        $file = $dirName . DIRECTORY_SEPARATOR . self::ORO_INSTALLER_FILE_NAME;
        if (is_file($file) && !isset($installers[md5($file)])) {
            $data = $this->getInstallerInfoFromFile($file);
            if ($data) {
                $data['index'] = $index;
                $index++;
                $installers[$data['file']] = $data;

                return $data;
            }
        }

        return false;
    }

    /**
     * Compare two installers for sorting
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function compareInstallers($a, $b)
    {
        $pathA = dirname($a['file']) . DIRECTORY_SEPARATOR;
        $pathB = dirname($b['file']) . DIRECTORY_SEPARATOR;

        if (strpos($pathA, $pathB) === 0) {
            return -1;
        } elseif (strpos($pathB, $pathA) === 0) {
            return 1;
        }

        return $a['index'] < $b['index'] ? -1 : 1;
    }

    /**
     * Get info about installer file
     *
     * @param string $fileName
     * @return array|bool
     */
    protected function getInstallerInfoFromFile($fileName)
    {
        $tokens =[];
        if (preg_match(
            '/@'.self::ORO_INSTALLER_STRING.'\("([A-Za-z0-9_ -]+)"\)/i',
            file_get_contents($fileName),
            $tokens
        )) {
            if (isset($tokens[1])) {
                return [
                    'key' => md5($fileName),
                    'file' => $fileName,
                    'label' => str_replace('"', '', $tokens[1])
                ];
            }
        }

        return false;
    }
}
