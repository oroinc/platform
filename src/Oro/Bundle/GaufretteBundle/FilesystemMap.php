<?php

namespace Oro\Bundle\GaufretteBundle;

use Gaufrette\FilesystemMapInterface;

/**
 * Copy of the Knp\Bundle\GaufretteBundle/FilesystemMap with the fix the issue
 * https://github.com/KnpLabs/KnpGaufretteBundle/pull/252
 *
 * Copyright (C) 2011 by Antoine Hérault <antoine.herault@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class FilesystemMap implements \IteratorAggregate, FilesystemMapInterface
{
    /**
     * Map of filesystems indexed by their name.
     *
     * @var array
     */
    protected $maps;

    /**
     * Instantiates a new filesystem map.
     *
     * @param array $maps
     */
    public function __construct(array $maps)
    {
        $this->maps = $maps;
    }

    /**
     * Retrieves a filesystem by its name.
     *
     * @param string $name name of a filesystem
     *
     * @return \Gaufrette\Filesystem
     *
     * @throw \InvalidArgumentException if the filesystem does not exist
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(sprintf('No filesystem is registered for name "%s"', $name));
        }

        return $this->maps[$name];
    }

    /**
     * @param string $name name of a filesystem
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->maps[$name]);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->maps);
    }
}
