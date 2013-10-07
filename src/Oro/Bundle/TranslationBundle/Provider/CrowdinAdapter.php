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
     * @param $mode
     *
     * @return mixed array with xml strings
     */
    public function addFile($remotePath, $file, $mode = 'add')
    {
        $result = $this->request(
            sprintf('/project/%s/%s-file', $this->projectId, $mode),
            array(
                sprintf('files[%s]', $remotePath) => '@'.$file,
            ),
            'POST'
        );

        return $result;
    }

    /**
     * @param string $dir
     *
     * @throws \Exception
     * @return bool
     */
    public function addDirectory($dir)
    {
        try {
            $result = $this->request(
                '/project/'.$this->projectId.'/add-directory',
                array(
                    'name' => $dir,
                ),
                'POST'
            );
        } catch (\Exception $e) {
            $this->notifyProgress($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * @param array $dirs
     *
     * @return $this
     */
    public function createDirectories($dirs)
    {
        $i = 0;
        foreach ($dirs as $dir) {
            $result = $this->addDirectory($dir);

            $i++;
            $this->notifyProgress(
                sprintf('%0.2f%%', $i*100 / count($dirs)) .
                sprintf(
                    $result ? ' Directory <info>%s</info> created' : '',
                    $dir
                )
            );
        }

        return $this;
    }

    /**
     * @param $files
     * @param $mode
     *
     * @return array
     */
    public function uploadFiles($files, $mode)
    {
        $results = array();
        $failed = array();
        $i = 0;

        foreach ($files as $apiPath => $filePath) {
            try {
                $results[] = $this->addFile($apiPath, $filePath, $mode);
            } catch (\Exception $e) {
                $failed[$filePath] = $e->getMessage();
            }

            $i++;
            $this->notifyProgress(
                sprintf('%0.2f%%', $i*100 / count($files)),
                sprintf('File <info>%s</info> uploaded', $apiPath)
            );
        }

        return array('results' => $results, 'failed' => $failed);
    }

    /**
     * {@inheritdoc}
     */
    protected function request($uri, $data = array(), $method = 'GET')
    {
        $result = parent::request($uri, $data, $method);
        $result = new \SimpleXMLElement($result);

        if ($result->getName() == 'error') {
            $message = $result->message;
            throw new \Exception($message, (int)$result->code);
        }

        return true;
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
            $currentDir = '';
            foreach ($_dirs as $dir) {
                $currentDir .= '/' . $dir;
                $dirs[$currentDir] = $currentDir;
            }
        }

        return $this->createDirectories($dirs)
            ->uploadFiles($files, $mode);
    }

    /**
     * @param string $projectId
     */
    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    }
}
