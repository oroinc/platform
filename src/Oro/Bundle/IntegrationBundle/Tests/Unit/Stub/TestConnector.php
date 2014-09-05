<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Stub;

use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;

class TestConnector extends AbstractConnector
{
    /**
     * Returns type name, the same as registered in service tag
     *
     * @return string
     */
    public function getType()
    {
        // TODO: Implement getType() method.
    }

    /**
     * Returns label for UI
     *
     * @return string
     */
    public function getLabel()
    {
        // TODO: Implement getLabel() method.
    }

    /**
     * Returns job name for import
     *
     * @return string
     */
    public function getImportJobName()
    {
        // TODO: Implement getImportJobName() method.
    }

    /**
     * Returns entity name that will be used for matching "import processor"
     *
     * @return string
     */
    public function getImportEntityFQCN()
    {
        // TODO: Implement getImportEntityFQCN() method.
    }

    /**
     * Return source iterator to read from
     *
     * @return \Iterator
     */
    protected function getConnectorSource()
    {
        return new TestIterator();
    }
}
