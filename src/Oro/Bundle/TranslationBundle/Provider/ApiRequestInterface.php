<?php

namespace Oro\Bundle\TranslationBundle\Provider;

interface ApiRequestInterface
{
    /**
     * Set curl options
     *
     * @param array $options
     *
     * @return bool
     */
    public function setOptions(array $options);

    /**
     * Execute request and return result
     *
     * @throws \RuntimeException
     * @return mixed
     */
    public function execute();

    /**
     * Close curl resource
     *
     * @return void
     */
    public function close();

    /**
     * @return mixed
     */
    public function reset();
}
