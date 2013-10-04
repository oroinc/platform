<?php

namespace Oro\Bundle\CrowdinBundle\Provider;

class CrowdinAdapter extends AbstractAPIAdapter
{
    /**
     * Add-file API method
     *
     * @param $file
     * @return mixed array with xml strings
     */
    public function addFile($file)
    {
        $result = $this->request(
            '/project/'.$this->projectId.'/add-file',
            array(
                sprintf('files[%s]', basename($file)) => '@'.$file,
            )
        );

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function upload($files)
    {
        if (empty($files)) {
            return false;
        }

        $results = array();
        $failed = array();
        foreach ($files as $filePath) {
            try {
                $results[] = $this->addFile($filePath);
            } catch (\Exception $e) {
                $failed[$filePath] = $e->getMessage();
            }
        }

        return array('results' => $results, 'failed' => $failed);
    }
}
