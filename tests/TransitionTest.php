<?php

declare(strict_types=1);

namespace PhilipRehberger\StateMachine\Tests;

use PhilipRehberger\StateMachine\Exceptions\InvalidStateException;
use PhilipRehberger\StateMachine\Exceptions\TransitionNotAllowedException;
use PhilipRehberger\StateMachine\History\TransitionHistory;
use PhilipRehberger\StateMachine\StateMachine;
use PhilipRehberger\StateMachine\Tests\Fixtures\Order;
use PhilipRehberger\StateMachine\Transition;
use PhilipRehberger\StateMachine\TransitionResult;
use PHPUnit\Framework\TestCase;

class TransitionTest extends TestCase
{
    // ── Transition value object ──────────────────────────────────

    public function test_transition_stores_name(): void
    {
        $transition = new Transition('ship', ['processing'], 'shipped');

        $this->assertSame('ship', $transition->name);
    }

    public function test_transition_stores_from_states(): void
    {
        $transition = new Transition('cancel', ['pending', 'processing'], 'cancelled');

        $this->assertSame(['pending', 'processing'], $transition->from);
    }

    public function test_transition_stores_to_state(): void
    {
        $transition = new Transition('process', ['pending'], 'processing');

        $this->assertSame('processing', $transition->to);
    }

    public function test_transition_defaults_guards_to_empty(): void
    {
        $transition = new Transition('process', ['pending'], 'processing');

        $this->assertSame([], $transition->guards);
    }

    public function test_transition_defaults_before_hooks_to_empty(): void
    {
        $transition = new Transition('process', ['pending'], 'processing');

        $this->assertSame([], $transition->beforeHooks);
    }

    public function test_transition_defaults_after_hooks_to_empty(): void
    {
        $transition = new Transition('process', ['pending'], 'processing');

        $this->assertSame([], $transition->afterHooks);
    }

    public function test_transition_stores_guards(): void
    {
        $guard = fn (object $entity) => true;
        $transition = new Transition('process', ['pending'], 'processing', guards: [$guard]);

        $this->assertCount(1, $transition->guards);
        $this->assertSame($guard, $transition->guards[0]);
    }

    public function test_transition_stores_before_hooks(): void
    {
        $hook = fn (object $entity) => null;
        $transition = new Transition('process', ['pending'], 'processing', beforeHooks: [$hook]);

        $this->assertCount(1, $transition->beforeHooks);
        $this->assertSame($hook, $transition->beforeHooks[0]);
    }

    public function test_transition_stores_after_hooks(): void
    {
        $hook = fn (object $entity) => null;
        $transition = new Transition('process', ['pending'], 'processing', afterHooks: [$hook]);

        $this->assertCount(1, $transition->afterHooks);
        $this->assertSame($hook, $transition->afterHooks[0]);
    }

    public function test_transition_stores_multiple_guards(): void
    {
        $guard1 = fn (object $entity) => true;
        $guard2 = fn (object $entity) => false;
        $transition = new Transition('process', ['pending'], 'processing', guards: [$guard1, $guard2]);

        $this->assertCount(2, $transition->guards);
    }

    // ── TransitionResult value object ────────────────────────────

    public function test_result_stores_transition_name(): void
    {
        $order = new Order;
        $result = new TransitionResult('process', 'pending', 'processing', $order);

        $this->assertSame('process', $result->transition);
    }

    public function test_result_stores_from_state(): void
    {
        $order = new Order;
        $result = new TransitionResult('process', 'pending', 'processing', $order);

        $this->assertSame('pending', $result->from);
    }

    public function test_result_stores_to_state(): void
    {
        $order = new Order;
        $result = new TransitionResult('process', 'pending', 'processing', $order);

        $this->assertSame('processing', $result->to);
    }

    public function test_result_stores_entity_reference(): void
    {
        $order = new Order;
        $result = new TransitionResult('process', 'pending', 'processing', $order);

        $this->assertSame($order, $result->entity);
    }

    // ── TransitionHistory ────────────────────────────────────────

    public function test_history_all_returns_empty_array_initially(): void
    {
        $history = new TransitionHistory;

        $this->assertSame([], $history->all());
    }

    public function test_history_last_returns_null_when_empty(): void
    {
        $history = new TransitionHistory;

        $this->assertNull($history->last());
    }

    public function test_history_records_single_result(): void
    {
        $history = new TransitionHistory;
        $order = new Order;
        $result = new TransitionResult('process', 'pending', 'processing', $order);

        $history->record($result);

        $this->assertCount(1, $history->all());
        $this->assertSame($result, $history->all()[0]);
    }

    public function test_history_last_returns_most_recent(): void
    {
        $history = new TransitionHistory;
        $order = new Order;

        $result1 = new TransitionResult('process', 'pending', 'processing', $order);
        $result2 = new TransitionResult('ship', 'processing', 'shipped', $order);

        $history->record($result1);
        $history->record($result2);

        $this->assertSame($result2, $history->last());
    }

    public function test_history_records_multiple_results_in_order(): void
    {
        $history = new TransitionHistory;
        $order = new Order;

        $result1 = new TransitionResult('process', 'pending', 'processing', $order);
        $result2 = new TransitionResult('ship', 'processing', 'shipped', $order);
        $result3 = new TransitionResult('deliver', 'shipped', 'delivered', $order);

        $history->record($result1);
        $history->record($result2);
        $history->record($result3);

        $all = $history->all();
        $this->assertCount(3, $all);
        $this->assertSame('process', $all[0]->transition);
        $this->assertSame('ship', $all[1]->transition);
        $this->assertSame('deliver', $all[2]->transition);
    }

    public function test_history_tracks_full_transition_chain_via_state_machine(): void
    {
        $sm = StateMachine::define()
            ->states(['pending', 'processing', 'shipped', 'delivered'])
            ->initial('pending')
            ->stateProperty('state')
            ->transition('process', 'pending', 'processing')
            ->transition('ship', 'processing', 'shipped')
            ->transition('deliver', 'shipped', 'delivered')
            ->build();

        $order = new Order;

        $sm->apply($order, 'process');
        $sm->apply($order, 'ship');
        $sm->apply($order, 'deliver');

        $history = $sm->history();
        $all = $history->all();

        $this->assertCount(3, $all);
        $this->assertSame('pending', $all[0]->from);
        $this->assertSame('processing', $all[0]->to);
        $this->assertSame('processing', $all[1]->from);
        $this->assertSame('shipped', $all[1]->to);
        $this->assertSame('shipped', $all[2]->from);
        $this->assertSame('delivered', $all[2]->to);
    }

    public function test_history_result_references_correct_entity(): void
    {
        $sm = StateMachine::define()
            ->states(['pending', 'processing'])
            ->initial('pending')
            ->stateProperty('state')
            ->transition('process', 'pending', 'processing')
            ->build();

        $order = new Order;
        $sm->apply($order, 'process');

        $this->assertSame($order, $sm->history()->last()->entity);
    }

    // ── Exception classes ────────────────────────────────────────

    public function test_invalid_state_exception_message_contains_state(): void
    {
        $exception = new InvalidStateException('bogus');

        $this->assertStringContainsString('bogus', $exception->getMessage());
    }

    public function test_invalid_state_exception_extends_invalid_argument(): void
    {
        $exception = new InvalidStateException('bogus');

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }

    public function test_transition_not_allowed_exception_message_contains_transition_and_state(): void
    {
        $exception = new TransitionNotAllowedException('ship', 'pending');

        $this->assertStringContainsString('ship', $exception->getMessage());
        $this->assertStringContainsString('pending', $exception->getMessage());
    }

    public function test_transition_not_allowed_exception_extends_runtime(): void
    {
        $exception = new TransitionNotAllowedException('ship', 'pending');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
