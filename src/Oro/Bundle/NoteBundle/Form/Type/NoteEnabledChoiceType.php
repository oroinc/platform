<?php

namespace Oro\Bundle\NoteBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class NoteEnabledChoiceType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'note_choice';
    }

    /**
     * @inheritdoc
     */
    public function getParent()
    {
        return 'choice';
    }
}
