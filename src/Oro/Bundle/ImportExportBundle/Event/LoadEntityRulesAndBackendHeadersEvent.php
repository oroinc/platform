<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class LoadEntityRulesAndBackendHeadersEvent extends Event
{
    /** @var string */
    protected $entityName;

    /** @var array */
    protected $headers;

    /** @var array */
    protected $rules;

    /** @var string */
    protected $convertDelimiter;

    /** @var string */
    protected $conversionType;

    /** @var bool */
    protected $fullData;

    /**
     * @param string $entityName
     * @param array $headers
     * @param array $rules
     * @param string $convertDelimiter
     * @param string $conversionType
     * @param bool $fullData
     */
    public function __construct(
        $entityName,
        array $headers,
        array $rules,
        $convertDelimiter,
        $conversionType,
        $fullData = false
    ) {
        $this->entityName = $entityName;
        $this->headers = $headers;
        $this->rules = $rules;
        $this->convertDelimiter = $convertDelimiter;
        $this->conversionType = $conversionType;
        $this->fullData = $fullData;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * @return string
     */
    public function getConvertDelimiter()
    {
        return $this->convertDelimiter;
    }

    /**
     * @param array $header
     */
    public function addHeader($header)
    {
        $this->headers[] = $header;
    }

    /**
     * @param string $name
     * @param array $rule
     */
    public function setRule($name, array $rule)
    {
        $this->rules[$name] = $rule;
    }

    /**
     * @return string
     */
    public function getConversionType()
    {
        return $this->conversionType;
    }

    /**
     * @return bool
     */
    public function isFullData()
    {
        return $this->fullData;
    }
}
