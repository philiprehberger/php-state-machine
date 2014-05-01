<?php

declare(strict_types=1);

namespace PhilipRehberger\StateMachine\Tests;

use PhilipRehberger\StateMachine\Exceptions\InvalidStateException;
use PhilipRehberger\StateMachine\Exceptions\TransitionNotAllowedException;
use PhilipRehberger\StateMachine\StateMachine;
use PhilipRehberger\StateMachine\Tests\Fixtures\Order;
use PhilipRehberger\StateMachine\TransitionResult;
use PHPUnit\Framework\TestCase;

class StateMachineTest extends TestCase
{
    private function buildOrderMachine(): StateMachine
    {
        return StateMachine::define()
            ->states(['pending', 'processing', 'shipped', 'delivered', 'cancelled'])
            ->initial('pending')
            ->stateProperty('state')
            ->transition('process', 'pending', 'processing')
            ->transition('ship', 'processing', 'shipped')
            ->transition('deliver', 'shipped', 'delivered')
            ->transition('cancel', ['pending', 'processing'], 'cancelled')
            ->build();
    }

    public function test_define_returns_builder(): void
    {
        $sm = $this->buildOrderMachine();

        $this->assertInstanceOf(StateMachine::class, $sm);
    }

    public function test_current_state_returns_entity_state(): void
    {
        $sm = $this->buildOrderMachine();
        $order = new Order;

        $this->assertSame('pending', $sm->currentState($order));
    }

    public function test_apply_transitions_entity_to_new_state(): void
    {
        $sm = $this->buildOrderMachine();
        $order = new Order;

        $sm->apply($order, 'process');

        $this->assertSame('processing', $order->state);
    }

    public function test_apply_returns_transition_result(): void
    {
        $sm = $this->buildOrderMachine();
        $order = new Order;

        $result = $sm->apply($order, 'process');

        $this->assertInstanceOf(TransitionResult::class, $result);
        $this->assertSame('process', $result->transition);
        $this->assertSame('pending', $result->from);
        $this->assertSame('processing', $result->to);
        $this->assertSame($order, $result->entity);
    }

    public function test_apply_throws_when_transition_not_allowed(): void
    {
        $sm = $this->buildOrderMachine();
        $order = new Order;

        $this->expectException(TransitionNotAllowedException::class);
        $sm->apply($order, 'ship');
    }

    public function test_can_returns_true_for_valid_transition(): void
    {
        $sm = $this->buildOrderMachine();
        $order = new Order;

        $this->assertTrue($sm->can($order, 'process'));
    }

    public function test_can_returns_false_for_invalid_transition(): void
    {
        $sm = $this->buildOrderMachine();
        $order = new Order;

        $this->assertFalse($sm->can($order, 'ship'));
    }

    public function test_allowed_transitions_returns_valid_names(): void
    {
        $sm = $this->buildOrderMachine();
        $order = new Order;

        $allowed = $sm->allowedTransitions($order);

        $this->assertSame(['process', 'cancel'], $allowed);
    }

    public function test_transition_from_multiple_states(): void
    {
        $sm = $this->buildOrderMachine();

        $order1 = new Order;
        $this->assertTrue($sm->can($order1, 'cancel'));

        $order2 = new Order;
        $sm->apply($order2, 'process');
        $this->assertTrue($sm->can($order2, 'cancel'));
    }

    public function test_guard_blocks_transition_when_returning_false(): void
    {
        $sm = StateMachine::define()
            ->states(['pending', 'processing'])
            ->initial('pending')
            ->transition('process', 'pending', 'processing')
            ->guard(fn (object $order) => $order->isPaid)
            ->build();

        $order = new Order;
        $order->isPaid = false;

        $this->assertFalse($sm->can($order, 'process'));

        $this->expectException(TransitionNotAllowedException::class);
        $sm->apply($order, 'process');
    }

    public function test_guard_allows_transition_when_returning_true(): void
    {
        $sm = StateMachine::define()
            ->states(['pending', 'processing'])
            ->initial('pending')
            ->transition('process', 'pending', 'processing')
            ->guard(fn (object $order) => $order->isPaid)
            ->build();

        $order = new Order;
        $order->isPaid = true;

        $this->assertTrue($sm->can($order, 'process'));
        $sm->apply($order, 'process');
        $this->assertSame('processing', $order->state);
    }

    public function test_before_hook_executes_before_state_change(): void
    {
        $log = [];

        $sm = StateMachine::define()
            ->states(['pending', 'processing'])
            ->initial('pending')
            ->transition('process', 'pending', 'processing')
            ->before(function (object $order) use (&$log): void {
                $log[] = 'before:'.$order->state;
            })
            ->build();

        $order = new Order;
        $sm->apply($order, 'process');

        $this->assertSame(['before:pending'], $log);
    }

    public function test_after_hook_executes_after_state_change(): void
    {
        $log = [];

        $sm = StateMachine::define()
            ->states(['pending', 'processing'])
            ->initial('pending')
            ->transition('process', 'pending', 'processing')
            ->after(function (object $order) use (&$log): void {
                $log[] = 'after:'.$order->state;
            })
            ->build();

        $order = new Order;
        $sm->apply($order, 'process');

        $this->assertSame(['after:processing'], $log);
    }

    public function test_multiple_guards_must_all_pass(): void
    {
        $sm = StateMachine::define()
            ->states(['pending', 'processing'])
            ->initial('pending')
            ->transition('process', 'pending', 'processing')
            ->guard(fn (object $order) => $order->isPaid)
            ->guard(fn (object $order) => $order->hasItems)
            ->build();

        $order = new Order;
        $order->isPaid = true;
        $order->hasItems = false;

        $this->assertFalse($sm->can($order, 'process'));
    }

    public function test_history_records_transitions(): void
    {
        $sm = $this->buildOrderMachine();
        $order = new Order;

        $sm->apply($order, 'process');
        $sm->apply($order, 'ship');

        $history = $sm->history();
        $this->assertCount(2, $history->all());
        $this->assertSame('ship', $history->last()->transition);
    }

    public function test_history_last_returns_null_when_empty(): void
    {
        $sm = $this->buildOrderMachine();

        $this->assertNull($sm->history()->last());
    }

    public function test_invalid_state_throws_exception(): void
    {
        $sm = $this->buildOrderMachine();
        $order = new Order;
        $order->state = 'nonexistent';

        $this->expectException(InvalidStateException::class);
        $sm->currentState($order);
    }

    public function test_initial_state_is_accessible(): void
    {
        $sm = $this->buildOrderMachine();

        $this->assertSame('pending', $sm->initialState());
    }

    public function test_states_returns_all_defined_states(): void
    {
        $sm = $this->buildOrderMachine();

        $this->assertSame(
            ['pending', 'processing', 'shipped', 'delivered', 'cancelled'],
            $sm->states(),
        );
    }

    public function test_custom_state_property(): void
    {
        $sm = StateMachine::define()
            ->states(['draft', 'published'])
            ->initial('draft')
            ->stateProperty('status')
            ->transition('publish', 'draft', 'published')
            ->build();

        $entity = new class
        {
            public string $status = 'draft';
        };

        $sm->apply($entity, 'publish');
        $this->assertSame('published', $entity->status);
    }
}
