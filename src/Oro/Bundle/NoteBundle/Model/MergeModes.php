<?php

namespace Oro\Bundle\NoteBundle\Model;

/**
 * Class MergeModes
 * @package Oro\Bundle\NoteBundle\Model
 */
final class MergeModes
{
    /**
     * Selected value replaces value after merge
     */
    const NOTES_REPLACE = 'notes_replace';

    /**
     * Will unite all notes into one collection
     */
    const NOTES_UNITE = 'notes_unite';
}
