<?php

namespace Thunk\Verbs\Examples\Monopoly\States;

use Brick\Money\Money;
use Thunk\Verbs\Examples\Monopoly\Game\DeedCollection;
use Thunk\Verbs\State;

class PlayerState extends State
{
    public bool $setup = false;

    public Money $money;

    public DeedCollection $deeds;
}
