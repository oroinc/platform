<?php

namespace Oro\Bundle\TranslationBundle\Provider;

class CrowdinAdapter extends AbstractAPIAdapter
{
    /** Crowdin folder exists */
    const DIR_ALREADY_EXISTS = 13;

    /**
     * @var string
     */
    protected $projectId;

    /**
     * Add or update file API method
     *
     * @param string $remotePath Path in remove API service
     * @param string $file
     * @param string $mode       'add' or 'update'
     *
     * @return mixed array with xml strings
     */
    public function addFile($remotePath, $file, $mode = 'add')
    {
        $result = $this->request(
            sprintf('/project/%s/%s-file', $this->projectId, $mode),
            array(
                sprintf('files[%s]', $remotePath) => '@' . $file,
                sprintf('export_patterns[%s]', $remotePath) => preg_replace(
                    '#\.[\w_]{2,5}\.(\w+)$#',
                    '.%locale_with_underscore%.$1',
                    $remotePath
                ),
            ),
            'POST'
        );

        return $result;
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    public function addDirectory($dir)
    {
        $this->request(
            '/project/' . $this->projectId . '/add-directory',
            array(
                'name' => $dir,
            ),
            'POST'
        );

        return true;
    }

    /**
     * @param array $dirs
     *
     * @throws \Exception
     * @return $this
     */
    public function createDirectories($dirs)
    {
        $i = 0;
        foreach ($dirs as $dir) {
            try {
                $i++;
                $this->addDirectory($dir);

                $this->notifyProgress(
                    sprintf('%0.2f%%', $i * 100 / count($dirs)) .
                    sprintf(' Directory <info>%s</info> created', $dir)
                );
            } catch (\Exception $e) {
                if ($e->getCode() !== self::DIR_ALREADY_EXISTS) {
                    throw $e;
                }

                $this->notifyProgress(
                    sprintf('%0.2f%%', $i * 100 / count($dirs)) .
                    sprintf(' Directory <info>%s</info> already exists, skipping...', $dir)
                );
            }
        }

        return $this;
    }

    /**
     * @param string $files
     * @param string $mode 'add' or 'update'
     *
     * @return array
     */
    public function uploadFiles($files, $mode)
    {
        $results = array();
        $failed  = array();
        $i       = 0;

        foreach ($files as $apiPath => $filePath) {
            try {
                $results[] = $this->addFile($apiPath, $filePath, $mode);
                $message   = sprintf('File <info>%s</info> uploaded', $apiPath);
            } catch (\Exception $e) {
                $failed[$filePath] = $e->getMessage();
                $message           = sprintf(
                    'File <info>%s</info> upload failed: <error>%s</error>',
                    $apiPath,
                    $e->getMessage()
                );
            }

            $i++;
            $this->notifyProgress(
                sprintf('%0.2f%%', $i * 100 / count($files)),
                $message
            );
        }

        return array('results' => $results, 'failed' => $failed);
    }

    /**
     * {@inheritdoc}
     */
    protected function request($uri, $data = array(), $method = 'GET', $curlOptions = [])
    {
        $result = parent::request($uri, $data, $method, $curlOptions);
        if (!isset($curlOptions[CURLOPT_FILE])) {
            $result = new \SimpleXMLElement($result);
            if ($result->getName() == 'error') {
                $message = $result->message;
                throw new \Exception($message, (int)$result->code);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function upload($files, $mode = 'add')
    {
        if (empty($files)) {
            return false;
        }

        // compile dir list
        $dirs = array();
        foreach ($files as $apiPath => $filePath) {
            $_dirs = explode(DIRECTORY_SEPARATOR, dirname($apiPath));

            $currentDir = array();
            foreach ($_dirs as $dir) {
                $currentDir[] = $dir;
                $path         = implode('/', $currentDir); // crowdin understand only "/" as directory separator :)
                $dirs[$path]  = $path;
            }
        }

        return $this
            ->createDirectories($dirs)
            ->uploadFiles($files, $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function download($path, $package = 'all')
    {
        $fileHandler = fopen($path, 'wb');
        $result = $this->request(
            sprintf('/project/%s/download/%s.zip', $this->projectId, $package),
            [],
            'GET',
            [
                CURLOPT_FILE           => $fileHandler,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_HEADER         => false,
            ]
        );

        fclose($fileHandler);

        return $result;
    }

    /**
     * @param string $projectId
     */
    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    }
}
