<?php

namespace Oro\Bundle\ImportExportBundle\Converter;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\FormatConversionEvent;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;

abstract class AbstractTableDataConverter extends DefaultDataConverter
{
    const BACKEND_TO_FRONTEND = 'backend_to_frontend';
    const FRONTEND_TO_BACKEND = 'frontend_to_backend';

    /** @var EventDispatcherInterface|null */
    protected $dispatcher;

    /** @var array */
    protected $backendHeader;

    /** @var array */
    protected $backendToFrontendHeader;

    /** @var array */
    protected $headerConversionRules;

    /**
     * {@inheritDoc}
     */
    public function convertToExportFormat(array $exportedRecord, $skipNullValues = true)
    {
        $exportedRecord = $this->dispatchFormatConversionEvent(Events::BEFORE_EXPORT_FORMAT_CONVERSION, $exportedRecord)
            ->getRecord();

        $plainDataWithBackendHeader = parent::convertToExportFormat($exportedRecord, $skipNullValues);
        $filledPlainDataWithBackendHeader = $this->fillEmptyColumns(
            $this->receiveBackendHeader(),
            $plainDataWithBackendHeader
        );
        $filledPlainDataWithFrontendHints = $this->replaceKeys(
            $this->receiveBackendToFrontendHeader(),
            $filledPlainDataWithBackendHeader
        );

        return $this->dispatchFormatConversionEvent(
            Events::AFTER_EXPORT_FORMAT_CONVERSION,
            $exportedRecord,
            $filledPlainDataWithFrontendHints
        )
        ->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $importedRecord = $this->dispatchFormatConversionEvent(Events::BEFORE_IMPORT_FORMAT_CONVERSION, $importedRecord)
            ->getRecord();

        $plainDataWithFrontendHeader = $this->removeEmptyColumns($importedRecord, $skipNullValues);

        $frontendHeader = array_keys($plainDataWithFrontendHeader);
        $frontendToBackendHeader = $this->convertHeaderToBackend($frontendHeader);
        $plainDataWithBackendHeader = $this->replaceKeys(
            $frontendToBackendHeader,
            $plainDataWithFrontendHeader
        );
        $complexDataWithBackendHeader = parent::convertToImportFormat($plainDataWithBackendHeader, $skipNullValues);
        $filteredComplexDataWithBackendHeader = $this->filterEmptyArrays($complexDataWithBackendHeader);

        return $this->dispatchFormatConversionEvent(
            Events::AFTER_IMPORT_FORMAT_CONVERSION,
            $importedRecord,
            $filteredComplexDataWithBackendHeader
        )
        ->getResult();
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string $eventName
     * @param array $record
     * @param array $result
     *
     * @return FormatConversionEvent
     */
    protected function dispatchFormatConversionEvent($eventName, array $record, array $result = [])
    {
        $event = new FormatConversionEvent($record, $result);
        if ($this->dispatcher && $this->dispatcher->hasListeners($eventName)) {
            $this->dispatcher->dispatch($eventName, $event);
        }

        return $event;
    }

    /**
     * @param array $header
     * @param array $data
     * @return array
     * @throws LogicException
     */
    protected function fillEmptyColumns(array $header, array $data)
    {
        $dataDiff = array_diff(array_keys($data), $header);
        // if data contains keys that are not in header
        if ($dataDiff) {
            throw new LogicException(
                sprintf('Backend header doesn\'t contain fields: %s', implode(', ', $dataDiff))
            );
        }

        $result = array();
        foreach ($header as $headerKey) {
            $result[$headerKey] = array_key_exists($headerKey, $data) ? $data[$headerKey] : '';
        }

        return $result;
    }

    /**
     * @param array $data
     * @param bool $skipNullValues
     * @return array
     */
    protected function removeEmptyColumns(array $data, $skipNullValues)
    {
        $data = array_map(
            function ($value) {
                if ($value === '') {
                    return null;
                }

                return $value;
            },
            $data
        );

        return array_filter(
            $data,
            function ($value) use ($skipNullValues) {
                if (is_null($value) && $skipNullValues) {
                    return false;
                }

                return true;
            }
        );
    }

    /**
     * @return array
     */
    protected function receiveBackendHeader()
    {
        if (null === $this->backendHeader) {
            $this->backendHeader = $this->getBackendHeader();
        }

        return $this->backendHeader;
    }

    /**
     * @return array
     */
    protected function receiveBackendToFrontendHeader()
    {
        if (null === $this->backendToFrontendHeader) {
            $header = $this->receiveBackendHeader();
            $this->backendToFrontendHeader = $this->convertHeaderToFrontend($header);
        }

        return $this->backendToFrontendHeader;
    }

    /**
     * @return array
     */
    protected function receiveHeaderConversionRules()
    {
        if (null === $this->headerConversionRules) {
            $this->headerConversionRules = $this->getHeaderConversionRules();
        }

        return $this->headerConversionRules;
    }

    /**
     * @param array $backendHeader
     * @return array
     */
    protected function convertHeaderToFrontend(array $backendHeader)
    {
        return $this->convertHeader($backendHeader, self::BACKEND_TO_FRONTEND);
    }

    /**
     * @param array $frontendHeader
     * @return array
     */
    protected function convertHeaderToBackend(array $frontendHeader)
    {
        return $this->convertHeader($frontendHeader, self::FRONTEND_TO_BACKEND);
    }

    /**
     * @param array $header
     * @param string $direction
     * @return array
     */
    protected function convertHeader(array $header, $direction)
    {
        $conversionRules = $this->receiveHeaderConversionRules();
        $result = array();

        foreach ($header as $hint) {
            $convertedHint = $hint;
            foreach ($conversionRules as $frontendHint => $backendHint) {
                // if regexp should be used
                if (is_array($backendHint)) {
                    if (!empty($backendHint[$direction])) {
                        $convertedHint = $this->applyRegexpConvert($backendHint[$direction], $hint);
                        // only one regexp should be applied
                        if ($convertedHint != $hint) {
                            break;
                        }
                    }
                } elseif ($direction == self::BACKEND_TO_FRONTEND && $hint == $backendHint) {
                    $convertedHint = $frontendHint;
                    break;
                } elseif ($direction == self::FRONTEND_TO_BACKEND && $hint == $frontendHint) {
                    $convertedHint = $backendHint;
                    break;
                }
            }

            $result[$hint] = $convertedHint;
        }

        return $result;
    }

    /**
     * @param array $parameters
     * @param string $value
     * @return string
     */
    protected function applyRegexpConvert(array $parameters, $value)
    {
        if (!empty($parameters[0]) && !empty($parameters[1])) {
            if (is_array($parameters[1]) && is_callable($parameters[1], true) || $parameters[1] instanceof \Closure) {
                $value = preg_replace_callback('~^' . $parameters[0] . '$~', $parameters[1], $value);
            } else {
                $value = preg_replace('~^' . $parameters[0] . '$~', $parameters[1], $value);
            }
        }

        return $value;
    }

    /**
     * Replace keys in data array according to array of replacements
     *
     * @param array $replacementKeys
     * @param array $data
     * @return array
     */
    protected function replaceKeys(array $replacementKeys, array $data)
    {
        $resultData = array();

        foreach ($data as $key => $value) {
            $resultKey = !empty($replacementKeys[$key]) ? $replacementKeys[$key] : $key;
            $resultData[$resultKey] = $value;
        }

        return $resultData;
    }

    /**
     * Remove all empty arrays and arrays with only null values
     *
     * @param array $data
     * @return array|null
     */
    protected function filterEmptyArrays(array $data)
    {
        $hasValue = false;

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->filterEmptyArrays($value);
                $data[$key] = $value;
            }

            if (array() === $value) {
                unset($data[$key]);
            } elseif (null !== $value) {
                $hasValue = true;
            }
        }

        return $hasValue ? $data : array();
    }

    /**
     * Get list of rules that should be user to convert,
     *
     * Example: array(
     *     'User Name' => 'userName', // key is frontend hint, value is backend hint
     *     'User Group' => array(     // convert data using regular expression
     *         self::FRONTEND_TO_BACKEND => array('User Group (\d+)', 'userGroup:$1'),
     *         self::BACKEND_TO_FRONTEND => array('userGroup:(\d+)', 'User Group $1'),
     *     )
     * )
     *
     * @return array
     */
    abstract protected function getHeaderConversionRules();

    /**
     * Get maximum backend header for current entity
     *
     * @return array
     */
    abstract protected function getBackendHeader();
}
