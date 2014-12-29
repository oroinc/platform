<?php

namespace Oro\Bundle\NoteBundle\Migration\Extension;

/**
 * NoteExtensionAwareInterface should be implemented by migrations that depends on a NoteExtension.
 */
interface NoteExtensionAwareInterface
{
    /**
     * Sets the NoteExtension
     *
     * @param NoteExtension $noteExtension
     */
    public function setNoteExtension(NoteExtension $noteExtension);
}
