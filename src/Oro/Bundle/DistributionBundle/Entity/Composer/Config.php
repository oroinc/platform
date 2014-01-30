<?php

namespace Oro\Bundle\DistributionBundle\Entity\Composer;


use Symfony\Component\Validator\Constraints as Assert;
use Composer\Json\JsonFile;

class Config
{
    /**
     * @Assert\Regex(pattern = "/^[0-9a-f]+$/i", message= "Invalid token")
     * @var string
     */
    protected $oauth;

    /**
     * @Assert\Valid
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
        if (null === $this->oauth) {
            if (isset($this->data['config']['github-oauth'])) {
                unset($this->data['config']['github-oauth']['github.com']);
                // Fix json schema.
                if (empty($this->data['config']['github-oauth'])) {
                    $this->data['config']['github-oauth'] = new \stdClass();
                }
            }
        } else {
            $this->data['config']['github-oauth']['github.com'] = $this->oauth;
        }

        $repositories = [];
        foreach ($this->repositories as $repo) {
            $repositories[] = ['type' => $repo->getType(), 'url' => $repo->getUrl()];
        }
        $this->data['repositories'] = $repositories;

        $this->config->write($this->data);
    }

    /**
     * @param string $oauth
     */
    public function setOauth($oauth)
    {
        $this->oauth = $oauth;
    }

    /**
     * @return string
     */
    public function getOauth()
    {
        return $this->oauth;
    }

    /**
     * @return \Oro\Bundle\DistributionBundle\Entity\Composer\Repository[]
     */
    public function getRepositories()
    {
        return $this->repositories;
    }

    /**
     * @param \Oro\Bundle\DistributionBundle\Entity\Composer\Repository[] $repositories
     */
    public function setRepositories($repositories)
    {
        $this->repositories = $repositories;
    }

    protected function init()
    {
        $this->data = $this->config->read();

        if (!empty($this->data['repositories'])) {
            foreach ($this->data['repositories'] as $repoData) {
                $repo = new Repository();
                $repo->setUrl($repoData['url']);
                $repo->setType($repoData['type']);

                $this->repositories[] = $repo;
            }
        }

        if (isset($this->data['config']['github-oauth']['github.com'])) {
            $this->oauth = $this->data['config']['github-oauth']['github.com'];
        }
    }
}
