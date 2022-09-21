<?php

namespace Tests\Unit\Layouts;

use PHPUnit\Framework\TestCase;
use Marshmallow\NovaFlexibleContent\Layouts\Collection;
use Marshmallow\NovaFlexibleContent\Layouts\Layout;

class CollectionTest extends TestCase
{

    public function testFind(): void
    {
        $collection = new Collection([new Layout('Foo', 'bar')]);

        $this->assertNotNull($collection->find('bar'));
        $this->assertNull($collection->find('a-name'));
    }
}
