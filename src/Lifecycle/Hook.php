<?php

namespace Thunk\Verbs\Lifecycle;

use Closure;
use Illuminate\Contracts\Container\Container;
use ReflectionMethod;
use RuntimeException;
use SplObjectStorage;
use Thunk\Verbs\Attributes\Hooks\DeferFor;
use Thunk\Verbs\Event;
use Thunk\Verbs\Support\DependencyResolver;
use Thunk\Verbs\Support\Reflector;
use Thunk\Verbs\Support\Wormhole;

class Hook
{
    public static function fromClassMethod(object $target, ReflectionMethod|string $method): static
    {
        if (is_string($method)) {
            $method = new ReflectionMethod($target, $method);
        }

        $hook = new static(
            callback: Closure::fromCallable([$target, $method->getName()]),
            events: Reflector::getEventParameters($method),
            states: Reflector::getStateParameters($method),
            name: $method->getName(),
        );

        return Reflector::applyHookAttributes($method, $hook);
    }

    public static function fromClosure(Closure $callback): static
    {
        $hook = new static(
            callback: $callback,
            events: Reflector::getEventParameters($callback),
            states: Reflector::getStateParameters($callback),
        );

        return Reflector::applyHookAttributes($callback, $hook);
    }

    public function __construct(
        public Closure          $callback,
        public array            $events = [],
        public array            $states = [],
        public SplObjectStorage $phases = new SplObjectStorage,
        public ?string          $name = null,
        public ?DeferFor        $deferred_attribute = null,
    ) {}

    public function forcePhases(Phase ...$phases): static
    {
        foreach ($phases as $phase) {
            $this->phases[$phase] = true;
        }

        return $this;
    }

    public function skipPhases(Phase ...$phases): static
    {
        foreach ($phases as $phase) {
            $this->phases[$phase] = false;
        }

        return $this;
    }

    public function runsInPhase(Phase $phase): bool
    {
        return isset($this->phases[$phase]) && $this->phases[$phase] === true;
    }

    public function validate(Container $container, Event $event): bool
    {
        if ($this->runsInPhase(Phase::Validate)) {
            return $this->execute($container, $event) ?? true;
        }

        throw new RuntimeException('Hook::validate called on a non-validation hook.');
    }

    public function apply(Container $container, Event $event): void
    {
        if ($this->runsInPhase(Phase::Apply)) {
            app(Wormhole::class)->warp($event, fn () => $this->execute($container, $event));
        }
    }

    public function fired(Container $container, Event $event): void
    {
        if ($this->runsInPhase(Phase::Fired)) {
            $this->execute($container, $event);
        }
    }

    public function handle(Container $container, Event $event): mixed
    {
        if ($this->runsInPhase(Phase::Handle)) {
            $callable = fn () => $this->execute($container, $event);
            if ($this->deferred_attribute && ! $this->deferred_attribute->replay_only) {
                app(DeferredWriteQueue::class)->addHook($event, $this->deferred_attribute, $callable);
            } else {
                return $this->execute($container, $event);
            }
        }

        return null;
    }

    public function replay(Container $container, Event $event): void
    {
        if ($this->runsInPhase(Phase::Replay)) {
            $callable = fn () => $this->execute($container, $event);
            if ($this->deferred_attribute) {
                app(DeferredWriteQueue::class)->addHook($event, $this->deferred_attribute, $callable);
            } else {
                app(Wormhole::class)->warp($event, $callable);
            }
        }
    }

    protected function execute(Container $container, Event $event): mixed
    {
        $resolver = DependencyResolver::for($this->callback, container: $container, event: $event);

        return call_user_func_array($this->callback, $resolver());
    }
}
