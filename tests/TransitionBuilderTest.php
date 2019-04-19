<?php

declare(strict_types=1);

namespace PhilipRehberger\StateMachine\Tests;

use PhilipRehberger\StateMachine\StateMachine;
use PhilipRehberger\StateMachine\StateMachineBuilder;
use PhilipRehberger\StateMachine\Tests\Fixtures\Order;
use PhilipRehberger\StateMachine\Transition;
use PHPUnit\Framework\TestCase;

class TransitionBuilderTest extends TestCase
{
    // ── buildTransition() output ─────────────────────────────────

    public function test_build_transition_returns_transition_instance(): void
    {
        $parent = new StateMachineBuilder;
        $builder = $parent->transition('process', 'pending', 'processing');

        $transition = $builder->buildTransition();

        $this->assertInstanceOf(Transition::class, $transition);
    }

    public function test_build_transition_sets_name(): void
    {
        $parent = new StateMachineBuilder;
        $builder = $parent->transition('process', 'pending', 'processing');

        $transition = $builder->buildTransition();

        $this->assertSame('process', $transition->name);
    }

    public function test_build_transition_wraps_string_from_in_array(): void
    {
        $parent = new StateMachineBuilder;
        $builder = $parent->transition('process', 'pending', 'processing');

        $transition = $builder->buildTransition();

        $this->assertSame(['pending'], $transition->from);
    }

    public function test_build_transition_preserves_array_from(): void
    {
        $parent = new StateMachineBuilder;
        $builder = $parent->transition('cancel', ['pending', 'processing'], 'cancelled');

        $transition = $builder->buildTransition();

        $this->assertSame(['pending', 'processing'], $transition->from);
    }

    public function test_build_transition_sets_to(): void
    {
        $parent = new StateMachineBuilder;
        $builder = $parent->transition('process', 'pending', 'processing');

        $transition = $builder->buildTransition();

        $this->assertSame('processing', $transition->to);
    }

    public function test_build_transition_has_empty_guards_by_default(): void
    {
        $parent = new StateMachineBuilder;
        $builder = $parent->transition('process', 'pending', 'processing');

        $transition = $builder->buildTransition();

        $this->assertSame([], $transition->guards);
    }

    public function test_build_transition_has_empty_before_hooks_by_default(): void
    {
        $parent = new StateMachineBuilder;
        $builder = $parent->transition('process', 'pending', 'processing');

        $transition = $builder->buildTransition();

        $this->assertSame([], $transition->beforeHooks);
    }

    public function test_build_transition_has_empty_after_hooks_by_default(): void
    {
        $parent = new StateMachineBuilder;
        $builder = $parent->transition('process', 'pending', 'processing');

        $transition = $builder->buildTransition();

        $this->assertSame([], $transition->afterHooks);
    }

    // ── guard() ──────────────────────────────────────────────────

    public function test_guard_returns_same_builder(): void
    {
        $parent = new StateMachineBuilder;
        $builder = $parent->transition('process', 'pending', 'processing');

        $result = $builder->guard(fn (object $entity) => true);

        $this->assertSame($builder, $result);
    }

    public function test_guard_adds_single_guard(): void
    {
        $parent = new StateMachineBuilder;
        $guard = fn (object $entity) => true;

        $builder = $parent->transition('process', 'pending', 'processing')
            ->guard($guard);

        $transition = $builder->buildTransition();

        $this->assertCount(1, $transition->guards);
        $this->assertSame($guard, $transition->guards[0]);
    }

    public function test_guard_chains_multiple_guards(): void
    {
        $parent = new StateMachineBuilder;
        $guard1 = fn (object $entity) => true;
        $guard2 = fn (object $entity) => false;

        $builder = $parent->transition('process', 'pending', 'processing')
            ->guard($guard1)
            ->guard($guard2);

        $transition = $builder->buildTransition();

        $this->assertCount(2, $transition->guards);
        $this->assertSame($guard1, $transition->guards[0]);
        $this->assertSame($guard2, $transition->guards[1]);
    }

    // ── before() ─────────────────────────────────────────────────

    public function test_before_returns_same_builder(): void
    {
        $parent = new StateMachineBuilder;
        $builder = $parent->transition('process', 'pending', 'processing');

        $result = $builder->before(fn (object $entity) => null);

        $this->assertSame($builder, $result);
    }

    public function test_before_adds_single_hook(): void
    {
        $parent = new StateMachineBuilder;
        $hook = fn (object $entity) => null;

        $builder = $parent->transition('process', 'pending', 'processing')
            ->before($hook);

        $transition = $builder->buildTransition();

        $this->assertCount(1, $transition->beforeHooks);
        $this->assertSame($hook, $transition->beforeHooks[0]);
    }

    public function test_before_chains_multiple_hooks(): void
    {
        $parent = new StateMachineBuilder;
        $hook1 = fn (object $entity) => null;
        $hook2 = fn (object $entity) => null;

        $builder = $parent->transition('process', 'pending', 'processing')
            ->before($hook1)
            ->before($hook2);

        $transition = $builder->buildTransition();

        $this->assertCount(2, $transition->beforeHooks);
    }

    // ── after() ──────────────────────────────────────────────────

    public function test_after_returns_same_builder(): void
    {
        $parent = new StateMachineBuilder;
        $builder = $parent->transition('process', 'pending', 'processing');

        $result = $builder->after(fn (object $entity) => null);

        $this->assertSame($builder, $result);
    }

    public function test_after_adds_single_hook(): void
    {
        $parent = new StateMachineBuilder;
        $hook = fn (object $entity) => null;

        $builder = $parent->transition('process', 'pending', 'processing')
            ->after($hook);

        $transition = $builder->buildTransition();

        $this->assertCount(1, $transition->afterHooks);
        $this->assertSame($hook, $transition->afterHooks[0]);
    }

    public function test_after_chains_multiple_hooks(): void
    {
        $parent = new StateMachineBuilder;
        $hook1 = fn (object $entity) => null;
        $hook2 = fn (object $entity) => null;

        $builder = $parent->transition('process', 'pending', 'processing')
            ->after($hook1)
            ->after($hook2);

        $transition = $builder->buildTransition();

        $this->assertCount(2, $transition->afterHooks);
    }

    // ── Mixed chaining ──────────────────────────────────────────

    public function test_guard_before_after_chain(): void
    {
        $parent = new StateMachineBuilder;
        $guard = fn (object $entity) => true;
        $beforeHook = fn (object $entity) => null;
        $afterHook = fn (object $entity) => null;

        $builder = $parent->transition('process', 'pending', 'processing')
            ->guard($guard)
            ->before($beforeHook)
            ->after($afterHook);

        $transition = $builder->buildTransition();

        $this->assertCount(1, $transition->guards);
        $this->assertCount(1, $transition->beforeHooks);
        $this->assertCount(1, $transition->afterHooks);
    }

    // ── transition() delegation ─────────────────────────────────

    public function test_transition_delegates_to_parent_builder(): void
    {
        $sm = StateMachine::define()
            ->states(['pending', 'processing', 'shipped'])
            ->initial('pending')
            ->transition('process', 'pending', 'processing')
            ->guard(fn (object $entity) => true)
            ->transition('ship', 'processing', 'shipped')
            ->build();

        $order = new Order;

        $sm->apply($order, 'process');
        $sm->apply($order, 'ship');

        $this->assertSame('shipped', $order->state);
    }

    // ── build() delegation ──────────────────────────────────────

    public function test_build_from_transition_builder_returns_state_machine(): void
    {
        $sm = StateMachine::define()
            ->states(['pending', 'processing'])
            ->initial('pending')
            ->transition('process', 'pending', 'processing')
            ->build();

        $this->assertInstanceOf(StateMachine::class, $sm);
    }

    public function test_build_from_transition_builder_with_hooks_produces_working_machine(): void
    {
        $log = [];

        $sm = StateMachine::define()
            ->states(['pending', 'processing'])
            ->initial('pending')
            ->transition('process', 'pending', 'processing')
            ->guard(fn (object $order) => $order->isPaid)
            ->before(function (object $order) use (&$log): void {
                $log[] = 'before:'.$order->state;
            })
            ->after(function (object $order) use (&$log): void {
                $log[] = 'after:'.$order->state;
            })
            ->build();

        $order = new Order;
        $order->isPaid = true;

        $sm->apply($order, 'process');

        $this->assertSame('processing', $order->state);
        $this->assertSame(['before:pending', 'after:processing'], $log);
    }
}
