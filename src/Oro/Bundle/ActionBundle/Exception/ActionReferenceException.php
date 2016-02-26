<?php

namespace Oro\Bundle\ActionBundle\Exception;

class ActionReferenceException extends \LogicException
{
    protected $path;

    public static function circularPath(array $path, $delimiter = '.')
    {
        $static = new static(sprintf('Circular reference detected. Path: %s', implode($delimiter, $path)));
        $static->path = $path;

        return $static;
    }

    public static function maxDepthPath(array $path, $maxDepth, $delimiter = '.')
    {
        $static = new static(sprintf(
            'Max depth (%d) of reference reached by path: %s',
            $maxDepth,
            implode($delimiter, $path)
        ));
        $static->path = $path;

        return $static;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }
}
