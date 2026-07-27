<?php

declare(strict_types=1);

namespace BulletDigitalSolutions\Gunshot\Tests\Unit\Builders;

use BulletDigitalSolutions\Gunshot\Builders\FilterStringBuilder;
use PHPUnit\Framework\TestCase;

final class FilterStringBuilderTest extends TestCase
{
    public function test_it_builds_simple_where_filter(): void
    {
        $engine = new FakeEngine();
        $builder = new FilterStringBuilder($engine);

        $builder->where('status', 'active');

        $this->assertCount(1, $builder->getFilters());
        $this->assertSame('toString', (string) $builder);
        $this->assertSame($builder->getFilters(), $engine->filters);
    }

    public function test_it_builds_where_in_filter(): void
    {
        $engine = new FakeEngine();
        $builder = new FilterStringBuilder($engine);

        $builder->whereIn('id', [1, 2, 3]);

        $filters = $builder->getFilters();
        $this->assertSame('where_in', $filters[0]['type']);
        $this->assertSame([1, 2, 3], $filters[0]['value']);
    }

    public function test_it_builds_nested_groups(): void
    {
        $engine = new FakeEngine();
        $builder = new FilterStringBuilder($engine);

        $builder->where(function (FilterStringBuilder $query) {
            $query->where('a', '1')->orWhere('b', '2');
        });

        $filters = $builder->getFilters();
        $this->assertSame('sub', $filters[0]['type']);
        $this->assertCount(2, $filters[0]['filters']);
    }
}

class FakeEngine
{
    public array $filters = [];

    public function toString(array $filters): string
    {
        $this->filters = $filters;

        return 'toString';
    }
}
