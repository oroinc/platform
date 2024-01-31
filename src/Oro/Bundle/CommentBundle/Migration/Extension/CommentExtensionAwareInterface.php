<?php

namespace Oro\Bundle\CommentBundle\Migration\Extension;

/**
 * This interface should be implemented by migrations that depend on {@see CommentExtension}.
 */
interface CommentExtensionAwareInterface
{
    public function setCommentExtension(CommentExtension $commentExtension);
}
