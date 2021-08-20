<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;

abstract class AbstractFixtureWithTags extends AbstractFixture
{
    protected function getTextWithTags(): string
    {
        return 'Name with <sTy<st yle id="sample_id">Le></st></style><script attr="not_closed>tag</script>';
    }
}
