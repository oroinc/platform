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
     * Add-file API method
     *
     * @param string $remotePath Path in remove API service
     * @param string $file
     *
     * @return mixed array with xml strings
     */
    public function addFile($remotePath, $file)
    {
        $result = $this->request(
            '/project/'.$this->projectId.'/add-file',
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
            if ($e->getCode() == self::DIR_ALREADY_EXISTS) {
                return false;
            }

            throw $e;
        }

        return true;
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
    public function upload($files)
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

        // create remote dirs
        foreach ($dirs as $dir) {
            if ($this->addDirectory($dir)) {
                $this->notifyProgress(sprintf('Directory "%s" created', $dir));
            }
        }

        $results = array();
        $failed = array();
        foreach ($files as $apiPath => $filePath) {
            try {
                $results[] = $this->addFile($apiPath, $filePath);
            } catch (\Exception $e) {
                $failed[$filePath] = $e->getMessage();
            }

            $this->notifyProgress(sprintf('File "%s" uploaded', $apiPath));
        }

        return array('results' => $results, 'failed' => $failed);
    }

    /**
     * @param string $projectId
     */
    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    }
}
