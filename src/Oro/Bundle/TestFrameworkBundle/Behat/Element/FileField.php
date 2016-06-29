<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

class FileField extends Element
{
    /**
     * {@inheritdoc}
     */
    public function setValue($path)
    {
        $input = $this->find('css', 'input[type="file"]');
        $input->attachFile($path);
    }
}
