<?php

declare(strict_types=1);

namespace Local\Equipment\Crm;

/**
 * Данные для JS-счётчика оборудования в карточке сделки (BX.Local.Equipment.DealCount.init).
 */
final readonly class DealCountViewDTO implements \JsonSerializable
{
    public function __construct(
        public int $dealId,
        public int $count,
        public bool $highlight,
    ) {
    }

    /**
     * @return array{dealId: int, count: int, highlight: bool}
     */
    public function jsonSerialize(): array
    {
        return [
            'dealId' => $this->dealId,
            'count' => $this->count,
            'highlight' => $this->highlight,
        ];
    }
}
