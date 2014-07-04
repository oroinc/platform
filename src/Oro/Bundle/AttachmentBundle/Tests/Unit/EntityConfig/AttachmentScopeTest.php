<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EntityConfig;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;

class AttachmentScopeTest extends \PHPUnit_Framework_TestCase
{
    public function testScope()
    {
        $this->assertEquals(['file', 'image'], AttachmentScope::$attachmentTypes);
    }
}
