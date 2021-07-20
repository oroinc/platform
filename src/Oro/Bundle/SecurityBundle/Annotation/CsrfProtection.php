<?php

namespace Oro\Bundle\SecurityBundle\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * The CsrfProtection class handles the @CsrfProtection annotation
 *
 * @Annotation
 * @Target({"METHOD", "CLASS"})
 */
class CsrfProtection extends ConfigurationAnnotation
{
    const ALIAS_NAME = 'csrf_protection';

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var bool
     */
    protected $useRequest = false;

    /**
     * {@inheritdoc}
     */
    public function getAliasName()
    {
        return self::ALIAS_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function allowArray()
    {
        return false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return CsrfProtection
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isUseRequest(): bool
    {
        return $this->useRequest;
    }

    /**
     * @param bool $useRequest
     * @return CsrfProtection
     */
    public function setUseRequest($useRequest)
    {
        $this->useRequest = $useRequest;

        return $this;
    }
}
