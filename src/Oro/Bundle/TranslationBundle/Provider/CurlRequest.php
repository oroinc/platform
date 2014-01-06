<?php

namespace Oro\Bundle\TranslationBundle\Provider;

class CurlRequest implements ApiRequestInterface
{
    protected $handler;

    public function __construct($options = [])
    {
        $this->handler = curl_init();
        $this->setOptions($options);
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        if (empty($options)) {
            return false;
        }

        return curl_setopt_array($this->handler, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $result = curl_exec($this->handler);

        if (!$result) {
            $errorCode = curl_errno($this->handler);
            $error     = curl_error($this->handler);

            throw new \RuntimeException($error, $errorCode);
        }

        return $result;
    }

    /**
     * Reset options that may still be in place after previous calls
     * as curl_reset available only in php5.5, just re-init curl handler
     */
    public function reset()
    {
        $this->close();
        $this->handler = curl_init();
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        curl_close($this->handler);
    }
}
