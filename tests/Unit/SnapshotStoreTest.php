<?php

use Glhd\Bits\Bits;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Uid\AbstractUid;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Contracts\StoresSnapshots;
use Thunk\Verbs\Event;
use Thunk\Verbs\Models\VerbSnapshot;
use Thunk\Verbs\State;

it('can store multiple different states with the same ID', function () {
    $store = app(StoresSnapshots::class);

    $state1 = new SnapshotStoreTestStateOne(1, 'state one');
    $state2 = new SnapshotStoreTestStateTwo(1, 'state two');

    $store->write([$state1, $state2]);

    $snapshot1 = VerbSnapshot::where(['type' => SnapshotStoreTestStateOne::class, 'state_id' => 1])->sole();
    $snapshot2 = VerbSnapshot::where(['type' => SnapshotStoreTestStateTwo::class, 'state_id' => 1])->sole();

    expect($snapshot1)->state()->name->toBe('state one')
        ->and($snapshot2)->state()->name->toBe('state two')
        ->and($snapshot1)->id->not()->toBe($snapshot2->id);
});

it('loads snapshots based on the state_id', function () {
    SnapshotStoreTestEvent::commit(
        state_id: 321,
        name: 'event one',
    );

    $store = app(StoresSnapshots::class);

    $state = $store->load(321, SnapshotStoreTestDifferentState::class);

    expect($state)
        ->id->toBe(321)
        ->name->toBe('event one');

    $states = $store->load([321], SnapshotStoreTestDifferentState::class);

    expect($states)
        ->count()->toBe(1);

    expect($states->first())
        ->id->toBe(321)
        ->name->toBe('event one');
});

class SnapshotStoreTestStateOne extends State
{
    public function __construct(
        public Bits|UuidInterface|AbstractUid|int|string|null $id,
        public string $name,
    ) {}
}

class SnapshotStoreTestStateTwo extends State
{
    public function __construct(
        public Bits|UuidInterface|AbstractUid|int|string|null $id,
        public string $name,
    ) {}
}

class SnapshotStoreTestEvent extends Event
{
    #[StateId(SnapshotStoreTestDifferentState::class)]
    public int $state_id;

    public string $name;

    public function apply(SnapshotStoreTestDifferentState $state)
    {
        $state->name = $this->name;
    }
}

class SnapshotStoreTestDifferentState extends State
{
    public Bits|UuidInterface|AbstractUid|int|string|null $id;

    public string $name;
}
