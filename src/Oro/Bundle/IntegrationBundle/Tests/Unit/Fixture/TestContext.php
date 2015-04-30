<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

class TestContext implements ContextInterface
{
    /**
     * {@inheritdoc}
     */
    public function addError($message)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function addErrors(array $messages)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getFailureExceptions()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function incrementReadCount($incrementBy = 1)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getReadCount()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementReadOffset()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getReadOffset()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function incrementAddCount($incrementBy = 1)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getAddCount()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementUpdateCount($incrementBy = 1)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateCount()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementReplaceCount($incrementBy = 1)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getReplaceCount()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementDeleteCount($incrementBy = 1)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getDeleteCount()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementErrorEntriesCount($incrementBy = 1)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getErrorEntriesCount()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($name, $value)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getValue($name)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function hasOption($name)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getOption($name, $default = null)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function removeOption($name)
    {
    }
}
