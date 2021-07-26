<?php

namespace Oro\Bundle\SecurityBundle\Acl\Permission;

/**
 * The base abstract class for different sort of permission mask builders which allows you
 * to build cumulative permissions easily, or convert masks to a human-readable format.
 *
 * Usually when you create own mask builder you just need define MASK_* and CODE_*
 * constants in your class. Also you can redefine PATTERN_ALL_* constants if you want to
 * change the human-readable format of a bitmask created by your mask builder.
 *
 * For example if a mask builder defines the following constants:
 * <code>
 *       const MASK_VIEW = 1;
 *       const MASK_EDIT = 2;
 *       const CODE_VIEW = 'V';
 *       const CODE_EDIT = 'E';
 *       const PATTERN_ALL_OFF = '............................ example:....';
 * </code>
 * it can be used in way like this:
 * <code>
 *       $builder
 *           ->add('view');
 *           ->add('edit');
 *
 *       // int(3)
 *       var_dump($builder->get());
 *       // string(41) "............................ example:..EV"
 *       var_dump($builder->getPattern());
 *       // string(32) "..............................EV"
 *       var_dump($builder->getPattern(true));
 * </code>
 */
abstract class MaskBuilder
{
    /**
     * Defines a human-readable format of a bitmask
     * All characters are allowed here, but only a character defined in self::OFF constant
     * is interpreted as bit placeholder.
     */
    protected const PATTERN_ALL_OFF = '................................';

    /**
     * A symbol is used in PATTERN_ALL_* constants as a placeholder of a bit
     */
    protected const OFF = '.';

    /**
     * The default character is used in a human-readable format to show that a bit in the bitmask is set
     * If you want more readable character please define CODE_* constants in your mask builder class.
     */
    protected const ON = '*';

    /** @var int */
    protected $mask;

    /** @var MaskBuilderMap */
    protected $map;

    protected function __construct()
    {
        $this->reset();
        $this->buildMap();
    }

    private function buildMap()
    {
        $this->map = new MaskBuilderMap();
        $reflection = new \ReflectionClass(static::class);
        foreach ($reflection->getConstants() as $name => $mask) {
            if (str_starts_with($name, 'MASK_')) {
                $this->map->permission[substr($name, 5)] = $mask;
                $this->map->all[$name] = $mask;
            } elseif (str_starts_with($name, 'GROUP_')) {
                $this->map->group[substr($name, 6)] = $mask;
                $this->map->all[$name] = $mask;
            }
        }
    }

    /**
     * Gets the mask of this permission
     *
     * @return int
     */
    public function get()
    {
        return $this->mask;
    }

    /**
     * Adds a mask to the permission
     *
     * @param int|string $mask
     *
     * @return MaskBuilder
     *
     * @throws \InvalidArgumentException
     */
    public function add($mask)
    {
        $this->mask |= $this->parseMask($mask);

        return $this;
    }

    /**
     * Removes a mask from the permission
     *
     * @param int|string $mask
     *
     * @return MaskBuilder
     *
     * @throws \InvalidArgumentException
     */
    public function remove($mask)
    {
        $this->mask &= ~$this->parseMask($mask);

        return $this;
    }

    /**
     * @param int|string $mask
     *
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    protected function parseMask($mask)
    {
        if (\is_string($mask)) {
            $mask = $this->getMaskForPermission(strtoupper($mask));
        } elseif (!\is_int($mask)) {
            throw new \InvalidArgumentException('$mask must be a string or an integer.');
        }

        return $mask;
    }

    /**
     * Resets the builder
     *
     * @return MaskBuilder
     */
    public function reset()
    {
        $this->mask = 0;

        return $this;
    }

    /**
     * Gets a human-readable representation of this mask
     *
     * @return string
     */
    public function getPattern()
    {
        return static::getPatternFor($this->mask);
    }

    /**
     * Gets a human-readable representation of the given mask
     *
     * @param int $mask
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public static function getPatternFor($mask)
    {
        if (!\is_int($mask)) {
            throw new \InvalidArgumentException('$mask must be an integer.');
        }

        $pattern = static::PATTERN_ALL_OFF;
        $length = \strlen(static::PATTERN_ALL_OFF);
        $bitmask = str_pad(decbin($mask), $length, '0', STR_PAD_LEFT);

        for ($i = $length - 1, $p = \strlen($pattern) - 1; $i >= 0; $i--, $p--) {
            // skip non mask chars if any
            while ($p >= 0 && static::OFF !== $pattern[$p]) {
                $p--;
            }
            if ('1' === $bitmask[$i]) {
                $pattern[$p] = static::getCode(1 << ($length - $i - 1));
            }
        }

        return $pattern;
    }

    /**
     * Gets the code for the passed mask
     *
     * @param int $mask
     *
     * @return string
     */
    protected static function getCode($mask)
    {
        $reflection = new \ReflectionClass(static::class);
        foreach ($reflection->getConstants() as $name => $cMask) {
            if (!str_starts_with($name, 'MASK_')) {
                continue;
            }

            if ($mask === $cMask) {
                $cName = 'static::CODE_' . substr($name, 5);
                if (\defined($cName)) {
                    return \constant($cName);
                }
                $lastDelim = strrpos($name, '_');
                if ($lastDelim > 5) {
                    $cName = 'static::CODE_' . substr($name, 5, $lastDelim - 5);
                    if (\defined($cName)) {
                        return \constant($cName);
                    }
                }
            }
        }

        return static::ON;
    }

    /**
     * Checks whether a permission or a group is defined in this mask builder.
     * A permission name should be started with MASK_ prefix.
     * A group name should be started with GROUP_ prefix.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasMask($name)
    {
        return \array_key_exists($name, $this->map->all);
    }

    /**
     * Gets a mask for a permission or a group.
     * A permission name should be started with MASK_ prefix.
     * A group name should be started with GROUP_ prefix.
     *
     * @param string $name
     *
     * @return int
     */
    public function getMask($name)
    {
        return $this->map->all[$name];
    }

    /**
     * Checks whether a permission for the given group is defined in this mask builder.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasMaskForGroup($name)
    {
        return \array_key_exists($name, $this->map->group);
    }

    /**
     * Gets permission value for the given group.
     *
     * @param string $name
     *
     * @return int
     */
    public function getMaskForGroup($name)
    {
        return $this->map->group[$name];
    }

    /**
     * Checks whether a permission with the given name is defined in this mask builder.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasMaskForPermission($name)
    {
        return \array_key_exists($name, $this->map->permission);
    }

    /**
     * Gets permission value by its name.
     *
     * @param string $name
     *
     * @return int
     */
    public function getMaskForPermission($name)
    {
        return $this->map->permission[$name];
    }
}
