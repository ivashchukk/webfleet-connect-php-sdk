<?php

declare(strict_types=1);

namespace Webfleet\Connect\Value;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

final readonly class DateRange
{
    public DateTimeImmutable $from;

    public DateTimeImmutable $to;

    public function __construct(DateTimeInterface $from, DateTimeInterface $to)
    {
        $utc = new DateTimeZone('UTC');
        $this->from = DateTimeImmutable::createFromInterface($from)->setTimezone($utc);
        $this->to = DateTimeImmutable::createFromInterface($to)->setTimezone($utc);

        if ($this->to <= $this->from) {
            throw new InvalidArgumentException('The end of a date range must be after its start.');
        }
    }

    public function durationSeconds(): int
    {
        return $this->to->getTimestamp() - $this->from->getTimestamp();
    }

    public function exceedsCalendarMonth(): bool
    {
        return $this->to > $this->from->modify('+1 month');
    }

    public function exceedsCalendarYear(): bool
    {
        return $this->to > $this->from->modify('+1 year');
    }

    /** @return array{range_pattern: 'ud', rangefrom_string: string, rangeto_string: string} */
    public function toParameters(): array
    {
        return [
            'range_pattern' => 'ud',
            'rangefrom_string' => $this->from->format('Y-m-d\TH:i:sP'),
            'rangeto_string' => $this->to->format('Y-m-d\TH:i:sP'),
        ];
    }
}
