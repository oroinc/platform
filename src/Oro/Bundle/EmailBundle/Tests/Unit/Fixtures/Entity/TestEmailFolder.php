<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;

class TestEmailFolder extends EmailFolder
{
    public function __construct($id = null)
    {
        parent::__construct();
        $this->id = $id;
    }
}
