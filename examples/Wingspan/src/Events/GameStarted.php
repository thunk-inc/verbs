<?php

namespace Thunk\Verbs\Examples\Wingspan\Events;

use InvalidArgumentException;
use Thunk\Verbs\Attributes\Autodiscovery\AppliesToState;
use Thunk\Verbs\Event;
use Thunk\Verbs\Examples\Wingspan\Game\Board;
use Thunk\Verbs\Examples\Wingspan\States\GameState;
use Thunk\Verbs\Examples\Wingspan\States\PlayerState;

#[AppliesToState(GameState::class)]
#[AppliesToState(PlayerState::class)]
class GameStarted extends Event
{
    public ?int $game_id = null;

    public function __construct(
        public array $player_ids,
    ) {
        $player_count = count($this->player_ids);

        if ($player_count < 1 || $player_count > 5) {
            throw new InvalidArgumentException('Wingspan can be played with 1-5 players.');
        }
    }

    public function player(int $index): PlayerState
    {
        return $this->states()->ofType(PlayerState::class)->values()->get($index);
    }

    public function validate(GameState $game): bool
    {
        return ! $game->started;
    }

    public function applyToGame(GameState $game)
    {
        $game->started = true;
        $game->player_ids = $this->player_ids;
    }

    public function applyToPlayers(PlayerState $player)
    {
        $player->available_action_cubes = 8;
        $player->board = new Board();
    }
}