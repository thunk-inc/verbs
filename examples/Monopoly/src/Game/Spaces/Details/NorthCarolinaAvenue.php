<?php

namespace Thunk\Verbs\Examples\Monopoly\Game\Spaces\Details;

use Thunk\Verbs\Examples\Monopoly\Game\PropertyColor;
use Thunk\Verbs\Examples\Monopoly\Game\Spaces\PropertyDetails;

class NorthCarolinaAvenue extends PropertyDetails
{
    protected string $name = 'North Carolina Avenue';

    protected PropertyColor $color = PropertyColor::Green;

    protected int $position = 32;

    protected int $price = 300;

    /** @var int[] */
    protected array $rent = [26, 130, 390, 900, 1100, 1275];

    protected int $building_cost = 200;
}