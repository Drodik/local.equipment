<?php

declare(strict_types=1);

namespace Local\Equipment\Tests\Grid;

use Local\Equipment\Grid\FilterMapper;
use PHPUnit\Framework\TestCase;

final class FilterMapperTest extends TestCase
{
    private \Closure $id;

    protected function setUp(): void
    {
        $this->id = static fn(string $v): string => $v;
    }

    public function testEmptyFilterGivesEmptyArray(): void
    {
        self::assertSame([], FilterMapper::toConditions([], $this->id));
    }

    public function testFindAndNameSearchBySubstring(): void
    {
        self::assertSame([['UF_NAME', 'like', '%Cisco%']], FilterMapper::toConditions(['FIND' => 'Cisco'], $this->id));
        self::assertSame([['UF_NAME', 'like', '%Dell%']], FilterMapper::toConditions(['FIND' => '', 'UF_NAME' => 'Dell'], $this->id));
    }

    public function testTypeListToInFilter(): void
    {
        self::assertSame([['UF_TYPE', 'in', [1, 3]]], FilterMapper::toConditions(['UF_TYPE' => ['1', '3']], $this->id));
    }

    public function testDateRangeWithOneBound(): void
    {
        self::assertSame(
            [['UF_CREATED_TIME', '>=', '01.10.2026 00:00:00']],
            FilterMapper::toConditions(['UF_CREATED_TIME_from' => '01.10.2026 00:00:00', 'UF_CREATED_TIME_to' => ''], $this->id)
        );
    }

    public function testLikeWildcardsInSearchAreEscaped(): void
    {
        self::assertSame([['UF_NAME', 'like', '%100\\%\\_x%']], FilterMapper::toConditions(['FIND' => '100%_x'], $this->id));
    }

    public function testServiceKeysIgnored(): void
    {
        self::assertSame([], FilterMapper::toConditions([
            'PRESET_ID' => 'tmp',
            'FILTER_ID' => 'x',
            'FILTER_APPLIED' => true,
            'UF_CREATED_TIME_datesel' => 'RANGE',
        ], $this->id));
    }
}
