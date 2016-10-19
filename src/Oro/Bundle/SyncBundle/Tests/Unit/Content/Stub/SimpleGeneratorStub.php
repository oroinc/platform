<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Content\Stub;

use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;

class SimpleGeneratorStub implements TagGeneratorInterface
{
    /** @var string */
    protected $suffix;

    /**
     * @param $suffix
     */
    public function __construct($suffix)
    {
        $this->suffix = $suffix;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data)
    {
        return is_string($data) && strpos($data, 'test') !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($data, $includeCollectionTag = false, $processNestedData = false)
    {
        $tags = [$data . $this->suffix];
        if ($includeCollectionTag) {
            $tags[] = $data . $this->suffix . self::COLLECTION_SUFFIX;
        }

        return $tags;
    }
}
