<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CommentBundle\Controller\Api\Rest\CommentController;
use Oro\Bundle\CommentBundle\DependencyInjection\OroCommentExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroCommentExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroCommentExtension());

        $expectedDefinitions = [
            CommentController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
