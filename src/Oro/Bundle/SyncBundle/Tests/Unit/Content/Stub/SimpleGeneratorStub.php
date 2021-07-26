<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Content\Stub;

use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;

class SimpleGeneratorStub implements TagGeneratorInterface
{
    /** @var string */
    protected $suffix;

    public function __construct($suffix)
    {
        $this->suffix = $suffix;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data)
    {
        return \is_string($data) && str_contains($data, 'test');
    }

    /**
     * {@inheritdoc}
     */
    public function generate($data, $includeCollectionTag = false, $processNestedData = false)
    {
        $tags = [$data . '_' . $this->suffix];
        if ($includeCollectionTag) {
            $tags[] = $data . '_' . $this->suffix . self::COLLECTION_SUFFIX;
        }

        return $tags;
    }
}
