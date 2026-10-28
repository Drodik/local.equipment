<?php

declare(strict_types=1);

namespace Local\Equipment\Tests\Crm;

use Local\Equipment\Crm\DealPage;
use PHPUnit\Framework\TestCase;

final class DealPageTest extends TestCase
{
    public function testDetailsUrl(): void
    {
        self::assertSame(15, DealPage::extractDealId('/crm/deal/details/15/?IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER'));
    }

    public function testNewDealIsNull(): void
    {
        self::assertNull(DealPage::extractDealId('/crm/deal/details/0/'));
    }

    public function testOtherPagesNull(): void
    {
        self::assertNull(DealPage::extractDealId('/crm/deal/list/'));
        self::assertNull(DealPage::extractDealId('/crm/type/1030/details/3/'));
    }
}
