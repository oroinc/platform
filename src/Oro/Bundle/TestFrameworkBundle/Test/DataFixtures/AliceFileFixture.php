<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

class AliceFileFixture extends AliceFixture
{
    /** @var string */
    private $fileName;

    /**
     * @param string $fileName
     */
    public function __construct($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadData()
    {
        return $this->loader->load($this->fileName);
    }
}
