<?php

namespace Oro\Bundle\TestFrameworkBundle\Faker;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Faker\Generator;

/**
 * Inspired by {@see\Faker\ORM\Doctrine\ColumnTypeGuesser}.
 * Adds compatibility with the doctrine/persistence v2.
 */
class ColumnTypeGuesser
{
    /**
     * @var Generator
     */
    protected $generator;

    public function __construct(Generator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @param string        $fieldName
     * @param ClassMetadata $class
     * @return \Closure|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function guessFormat(string $fieldName, ClassMetadata $class)
    {
        $generator = $this->generator;
        $type = $class->getTypeOfField($fieldName);
        switch ($type) {
            case 'boolean':
                return function () use ($generator) {
                    return $generator->boolean;
                };
            case 'decimal':
                $size = $class->fieldMappings[$fieldName]['precision'] ?? 2;

                return function () use ($generator, $size) {
                    return $generator->randomNumber($size + 2) / 100;
                };
            case 'smallint':
                return function () use ($generator) {
                    return $generator->numberBetween(0, 65535);
                };
            case 'integer':
                return function () use ($generator) {
                    return $generator->numberBetween(0, 2147483647);
                };
            case 'bigint':
                return function () use ($generator) {
                    return $generator->numberBetween(0, PHP_INT_MAX);
                };
            case 'float':
                return function () use ($generator) {
                    return $generator->randomFloat();
                };
            case 'string':
                $size = $class->fieldMappings[$fieldName]['length'] ?? 255;

                return function () use ($generator, $size) {
                    return $generator->text($size);
                };
            case 'text':
                return function () use ($generator) {
                    return $generator->text;
                };
            case 'datetime':
            case 'date':
            case 'time':
                return function () use ($generator) {
                    return $generator->datetime;
                };
            case 'datetime_immutable':
            case 'date_immutable':
            case 'time_immutable':
                return function () use ($generator) {
                    return \DateTimeImmutable::createFromMutable($generator->datetime);
                };
            default:
                // no smart way to guess what the user expects here
                return null;
        }
    }
}
