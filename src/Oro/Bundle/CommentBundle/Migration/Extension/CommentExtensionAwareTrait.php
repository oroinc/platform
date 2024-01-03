<?php

namespace Oro\Bundle\CommentBundle\Migration\Extension;

/**
 * This trait can be used by migrations that implement {@see CommentExtensionAwareInterface}.
 */
trait CommentExtensionAwareTrait
{
    private CommentExtension $commentExtension;

    public function setCommentExtension(CommentExtension $commentExtension): void
    {
        $this->commentExtension = $commentExtension;
    }
}
