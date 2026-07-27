<?php

declare(strict_types=1);

namespace BulletDigitalSolutions\Gunshot\Tests\Unit\Builders\Engines;

use BulletDigitalSolutions\Gunshot\Builders\Engines\ElasticsearchFilterEngine;
use PHPUnit\Framework\TestCase;

final class ElasticsearchFilterEngineTest extends TestCase
{
    public function test_empty_filter_returns_wildcard(): void
    {
        $engine = new ElasticsearchFilterEngine();

        $this->assertSame('*', $engine->toString([]));
    }

    public function test_where_filter_converts_to_elasticsearch_syntax(): void
    {
        $engine = new ElasticsearchFilterEngine();

        $string = $engine->toString([
            ['type' => 'where', 'field' => 'status', 'value' => 'active', 'operator' => '='],
        ]);

        $this->assertSame('(status:active)', $string);
    }

    public function test_not_equal_operator_prefixes_with_not(): void
    {
        $engine = new ElasticsearchFilterEngine();

        $string = $engine->toString([
            ['type' => 'where', 'field' => 'status', 'value' => 'inactive', 'operator' => '!='],
        ]);

        $this->assertSame('NOT (status:inactive)', $string);
    }

    public function test_multiple_wheres_are_prefixed_with_and(): void
    {
        $engine = new ElasticsearchFilterEngine();

        $string = $engine->toString([
            ['type' => 'where', 'field' => 'status', 'value' => 'active', 'operator' => '='],
            ['type' => 'where', 'field' => 'role', 'value' => 'admin', 'operator' => '='],
        ]);

        $this->assertSame('(status:active) AND (role:admin)', $string);
    }

    public function test_or_where_is_prefixed_with_or(): void
    {
        $engine = new ElasticsearchFilterEngine();

        $string = $engine->toString([
            ['type' => 'or_where', 'field' => 'status', 'value' => 'pending', 'operator' => '='],
        ]);

        $this->assertSame('OR (status:pending)', $string);
    }

    public function test_where_in_joins_values_with_or(): void
    {
        $engine = new ElasticsearchFilterEngine();

        $string = $engine->toString([
            ['type' => 'where_in', 'field' => 'id', 'value' => [1, 2], 'operator' => '='],
        ]);

        $this->assertSame('((id:1) OR (id:2))', $string);
    }

    public function test_nested_sub_query_is_wrapped(): void
    {
        $engine = new ElasticsearchFilterEngine();

        $string = $engine->toString([
            ['type' => 'sub', 'filters' => [
                ['type' => 'where', 'field' => 'a', 'value' => '1', 'operator' => '='],
            ]],
        ]);

        $this->assertSame('((a:1))', $string);
    }
}
