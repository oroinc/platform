<?php

namespace Oro\Bundle\NoteBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class NoteEnabledChoiceType extends AbstractType
{
    const NAME   = 'note_choice';
    const PARENT = 'choice';

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @inheritdoc
     */
    public function getParent()
    {
        return self::PARENT;
    }
}
