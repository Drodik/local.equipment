<?php

declare(strict_types=1);

namespace Local\Equipment\Tests\Access;

use Local\Equipment\Access\HighlightPolicy;
use PHPUnit\Framework\TestCase;

final class HighlightPolicyTest extends TestCase
{
    public function testMemberIsHighlighted(): void
    {
        self::assertTrue(HighlightPolicy::isHighlighted([2, 12], 12));
    }

    public function testNonMemberNotHighlighted(): void
    {
        self::assertFalse(HighlightPolicy::isHighlighted([2, 3], 12));
    }

    public function testMissingGroup(): void
    {
        self::assertFalse(HighlightPolicy::isHighlighted([2, 12], null));
    }

    // CurrentUser::getUserGroups() отдаёт ID групп строками.
    public function testStringGroupIds(): void
    {
        self::assertTrue(HighlightPolicy::isHighlighted(['2', '12'], 12));
    }
}
