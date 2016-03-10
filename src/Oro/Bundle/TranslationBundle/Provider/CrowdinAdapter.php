<?php

namespace Oro\Bundle\TranslationBundle\Provider;

class CrowdinAdapter extends AbstractAPIAdapter
{
    const FILE_NOT_FOUND = 8;

    /**
     * Add or update file API method
     *
     * @param string $remotePath Path in remove API service
     * @param string $file       File path
     * @param string $mode       'add' or 'update'
     *
     * @return mixed array with xml strings
     */
    public function addFile($remotePath, $file, $mode = 'add')
    {
        $result = $this->request(
            sprintf('project/%s/%s-file?json=1', $this->projectId, $mode),
            [
                sprintf('files[%s]', $remotePath) => '@' . $file,
                sprintf('export_patterns[%s]', $remotePath) => preg_replace(
                    '#\.[\w_]{2,5}\.(\w+)$#',
                    '.%locale_with_underscore%.$1',
                    $remotePath
                ),
            ],
            'POST'
        );

        return $result->json();
    }

    /**
     * @param string $dir
     *
     * @throws \Exception on fail
     *
     * @return bool
     */
    public function addDirectory($dir)
    {
        $response = $this->request(
            'project/' . $this->projectId . '/add-directory?json=1',
            [
                'name' => $dir,
            ],
            'POST'
        );

        return $response->json();
    }

    /**
     * @param array $dirs
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function createDirectories($dirs)
    {
        $this->logger->info('Creating directories');

        foreach ($dirs as $index => $dir) {
            $current = $index + 1;
            $result  = $this->addDirectory($dir);

            if (false == $result['success'] && isset($result['error'])) {
                $this->logger->error(
                    sprintf(
                        '%0.2f%% [skipped] <info>%s</info> Error: %s',
                        $current * 100 / count($dirs),
                        $dir,
                        $result['error']['message']
                    )
                );
            } else {
                $this->logger->info(
                    sprintf('%0.2f%% [created] <info>%s</info> directory', $current * 100 / count($dirs), $dir)
                );
            }
        }

        return $this;
    }

    /**
     * @param string $files
     * @param string $mode 'add' or 'update'
     *
     * @return bool
     */
    public function uploadFiles($files, $mode)
    {
        $this->logger->info('Uploading files');

        $failed  = [];
        $current = 0;

        foreach ($files as $apiPath => $filePath) {
            $current++;
            $percent = $current * 100 / count($files);

            $result = null;
            try {
                $result = $this->addFile($apiPath, $filePath, $mode);

                $isNotFound = isset($result['error']) && self::FILE_NOT_FOUND == $result['error']['code'];
                if ($isNotFound && 'update' == $mode) {
                    $result = $this->addFile($apiPath, $filePath, 'add');
                }
            } catch (\Exception $e) {
                $failed[] = $filePath;
                $result = [
                    'success' => false,
                    'error'   => ['message' => $e->getMessage()],
                ];
            }

            if (isset($result['error'])) {
                $this->logger->error(
                    sprintf(
                        '%0.2f%% [failed] <info>%s</info> file upload: <error>%s</error>',
                        $percent,
                        $apiPath,
                        $result['error']['message']
                    )
                );
            } else {
                $this->logger->info(
                    sprintf('%0.2f%% [uploaded] <info>%s</info>', $percent, $apiPath)
                );
            }

        }

        $failedCount = count($failed);
        if ($failedCount > 0) {
            $this->logger->warning(sprintf('%d files were skipped', $failedCount));
        }

        return $failedCount > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function upload($files, $mode = 'add')
    {
        if (empty($files)) {
            return false;
        }

        $dirs = $this->getFileFolders(array_keys($files));

        return $this
            ->createDirectories($dirs)
            ->uploadFiles($files, $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function download($path, array $projects = [], $package = null)
    {
        $package = is_null($package) ? 'all' : str_replace('_', '-', $package);

        $result = $this->request(
            sprintf('project/%s/download/%s.zip', $this->projectId, $package),
            [],
            'GET',
            [
                'save_to' => $path,
            ]
        );

        return 200 == $result->getStatusCode();
    }
}
