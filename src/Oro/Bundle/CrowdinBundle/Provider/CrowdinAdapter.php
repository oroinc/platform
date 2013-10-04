<?php

namespace Oro\Bundle\CrowdinBundle\Provider;

class CrowdinAdapter extends TranslationAPIAdapter
{
    public function addFile($file)
    {
        try {
            $result = $this->request(
                '/project/'.$this->projectId.'/add-file',
                array(
                    'files[test.en.yml]' => $file,
                )
            );
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function upload($dir)
    {
        if (is_dir($dir)) {
            // find all files
        }

        $this->addFile($dir);
    }
}
