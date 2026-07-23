<?php

declare(strict_types=1);

namespace Webfleet\Connect\Dto;

enum TripMode: int
{
    case Unknown = 0;
    case Private = 1;
    case Business = 2;
    case Commute = 3;
    case OdometerCorrection = 4;
}
