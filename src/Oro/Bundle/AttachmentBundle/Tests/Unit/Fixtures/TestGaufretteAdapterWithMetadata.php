<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures;

use Gaufrette\Adapter;
use Gaufrette\Adapter\MetadataSupporter;

abstract class TestGaufretteAdapterWithMetadata implements Adapter, MetadataSupporter
{
}
