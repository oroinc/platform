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
    protected $installerScripts;

    /**
     * @param $bundles
     * @param $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Run installer script by installer key
     *
     * @param string $scriptKey
     * @param StreamOutput $output
     * @param ContainerInterface $containerInstance
     */
    public function runInstallerScript($scriptKey, StreamOutput $output, ContainerInterface $containerInstance)
    {
        $script = $this->getInstallerScriptByKey($scriptKey);
        if ($script) {
            $output->writeln('');
            $output->writeln(sprintf('[%s] Launching "%s" installer script', date('Y-m-d H:i:s'), $script['file']));

            if (is_file($script['file'])) {
                ob_start();
                $container = $containerInstance;
                include($script['file']);
                $scriptOutput = ob_get_contents();
                ob_clean();
                $output->writeln($scriptOutput);
            } else {
                $output->writeln('File "%s" not found', $script['file']);
            }
        } else {
            $output->writeln(sprintf('Installer "%s" was not found', $scriptKey));
        }
    }

    /**
     * get array with installer scripts keys and labels
     *
     * @return array
     *  key -> installer file md5 key
     *  value -> installer label
     */
    public function getInstallerScriptsLabels()
    {
        $scripts = [];
        $this->checkInstallerScriptsLoaded();

        if (!empty($this->installerScripts)) {
            foreach ($this->installerScripts as $installerScript) {
                $scripts[$installerScript['key']] = $installerScript['label'];
            }
        }

        return $scripts;
    }

    /**
     * Get installer script info array by installer script md5 key
     *
     * @param $scriptKey
     * @return array|bool
     */
    public function getInstallerScriptByKey($scriptKey)
    {
        $this->checkInstallerScriptsLoaded();

        if (!empty($this->installerScripts) && isset($this->installerScripts[$scriptKey])) {
            return $this->installerScripts[$scriptKey];
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
    public function getInstallerScriptList()
    {
        $this->checkInstallerScriptsLoaded();

        return $this->installerScripts;
    }

    /**
     * Checks if installer scripts was loaded and load they if needed
     */
    protected function checkInstallerScriptsLoaded()
    {
        if (!is_array($this->installerScripts)) {
            $this->processInstallerScripts();
        }
    }

    /**
     * Read installer scripts info from installer files
     */
    protected function processInstallerScripts()
    {
        $index = 0;
        $scripts = [];
        $this->installerScripts = [];
        $rootDir = realpath($this->kernel->getRootDir() . DIRECTORY_SEPARATOR.'..' . DIRECTORY_SEPARATOR);
        $bundles = $this->kernel->getBundles();
        foreach ($bundles as $bundle) {
            $bundleDirName = $bundle->getPath();
            $this->getInstallerScriptInfo($bundleDirName, $index, $scripts);

            $relativePathArray = explode(DIRECTORY_SEPARATOR, str_replace($rootDir, '', $bundleDirName));
            if ($relativePathArray[0] == '') {
                unset ($relativePathArray[0]);
            }
            for ($i = count($relativePathArray); $i >= 0; $i--) {
                unset($relativePathArray[$i]);
                $checkPath = $rootDir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $relativePathArray);
                if ($this->getInstallerScriptInfo($checkPath, $index, $scripts)) {
                    break;
                }
            }
        }
        if (!empty($scripts)) {
            usort($scripts, array($this, "compareInstallerScripts"));
            foreach ($scripts as $script) {
                $this->installerScripts[$script['key']] = $script;
            }
        }
    }

    /**
     * Get installer script info from dir
     *
     * @param string $dirName
     * @param int $index
     * @param array $scripts
     * @return array|bool
     */
    protected function getInstallerScriptInfo($dirName, &$index, &$scripts)
    {
        $file = $dirName . DIRECTORY_SEPARATOR . self::ORO_INSTALLER_FILE_NAME;
        if (is_file($file) && !isset($scripts[md5($file)])) {
            $data = $this->getInstallerScriptInfoFromFile($file);
            if ($data) {
                $data['index'] = $index;
                $index++;
                $scripts[$data['file']] = $data;

                return $data;
            }
        }

        return false;
    }

    /**
     * Compare two installer scripts for sorting
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function compareInstallerScripts($a, $b)
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
     * Get info about installer script file
     *
     * @param string $fileName
     * @return array|bool
     */
    protected function getInstallerScriptInfoFromFile($fileName)
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
