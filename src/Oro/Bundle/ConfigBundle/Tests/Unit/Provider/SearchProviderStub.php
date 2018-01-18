<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Provider\SearchProviderInterface;

class SearchProviderStub implements SearchProviderInterface
{
    /** array */
    private $data = [];

    /** @var bool */
    private $enabled = true;

    /**
     * @param array $data
     * @param bool $enabled
     */
    public function __construct(array $data = [], $enabled = true)
    {
        $this->data = $data;
        $this->enabled = $enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($name)
    {
        return $this->enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getData($name)
    {
        return $this->data;
    }
}
