<?php

namespace Oro\Bundle\DistributionBundle\Entity\Composer;


use Composer\Json\JsonFile;

class Config
{
    /**
     * @var string
     */
    protected $oauth;

    /**
     * @var Repository[]
     */
    protected $repositories = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var JsonFile
     */
    protected $config;

    /**
     * @param JsonFile $config
     */
    public function __construct(JsonFile $config)
    {
        $this->config = $config;

        $this->init();
    }

    public function flush()
    {
        $this->config->write($this->data);
    }

    /**
     * @param string $oauth
     */
    public function setOauth($oauth)
    {
        $this->data['config']['github-oauth']['github.com'] = $oauth;
    }

    /**
     * @return string
     */
    public function getOauth()
    {
        if (isset($this->data['config']['github-oauth']['github.com'])) {
            return $this->data['config']['github-oauth']['github.com'];
        }
        return null;
    }

    /**
     * @return \Oro\Bundle\DistributionBundle\Entity\Composer\Repository[]
     */
    public function getRepositories()
    {
        return $this->repositories;
    }

    protected function init()
    {
        $this->data = $this->config->read();

        if (!empty($this->data['repositories'])) {
            foreach ($this->data['repositories'] as $repoData) {
                $repo = new Repository();
                $repo->setUrl($repoData['url']);

                $this->repositories[] = $repo;
            }
        }
    }
}
