<?php

declare(strict_types=1);

namespace PhilipRehberger\StateMachine\Tests;

use PhilipRehberger\StateMachine\StateMachine;
use PhilipRehberger\StateMachine\Tests\Fixtures\Order;
use PHPUnit\Framework\TestCase;

class EntryExitHooksTest extends TestCase
{
    public function test_on_enter_hook_fires_on_transition(): void
    {
        $log = [];

        $sm = StateMachine::define()
            ->states(['pending', 'processing', 'shipped'])
            ->initial('pending')
            ->onEnter('processing', function (object $entity, string $transition) use (&$log): void {
                $log[] = "entered:processing:via:$transition";
            })
            ->transition('process', 'pending', 'processing')
            ->transition('ship', 'processing', 'shipped')
            ->build();

        $order = new Order;
        $sm->apply($order, 'process');

        $this->assertSame(['entered:processing:via:process'], $log);
    }

    public function test_on_exit_hook_fires_on_transition(): void
    {
        $log = [];

        $sm = StateMachine::define()
            ->states(['pending', 'processing', 'shipped'])
            ->initial('pending')
            ->onExit('pending', function (object $entity, string $transition) use (&$log): void {
                $log[] = "exited:pending:via:$transition";
            })
            ->transition('process', 'pending', 'processing')
            ->transition('ship', 'processing', 'shipped')
            ->build();

        $order = new Order;
        $sm->apply($order, 'process');

        $this->assertSame(['exited:pending:via:process'], $log);
    }

    public function test_exit_hooks_fire_before_enter_hooks(): void
    {
        $log = [];

        $sm = StateMachine::define()
            ->states(['pending', 'processing'])
            ->initial('pending')
            ->onExit('pending', function (object $entity, string $transition) use (&$log): void {
                $log[] = 'exit:pending';
            })
            ->onEnter('processing', function (object $entity, string $transition) use (&$log): void {
                $log[] = 'enter:processing';
            })
            ->transition('process', 'pending', 'processing')
            ->build();

        $order = new Order;
        $sm->apply($order, 'process');

        $this->assertSame(['exit:pending', 'enter:processing'], $log);
    }

    public function test_hooks_do_not_fire_for_unrelated_states(): void
    {
        $log = [];

        $sm = StateMachine::define()
            ->states(['pending', 'processing', 'shipped'])
            ->initial('pending')
            ->onEnter('shipped', function (object $entity, string $transition) use (&$log): void {
                $log[] = 'entered:shipped';
            })
            ->transition('process', 'pending', 'processing')
            ->transition('ship', 'processing', 'shipped')
            ->build();

        $order = new Order;
        $sm->apply($order, 'process');

        $this->assertSame([], $log);
    }

    public function test_multiple_enter_hooks_for_same_state(): void
    {
        $log = [];

        $sm = StateMachine::define()
            ->states(['pending', 'processing'])
            ->initial('pending')
            ->onEnter('processing', function (object $entity, string $transition) use (&$log): void {
                $log[] = 'hook1';
            })
            ->onEnter('processing', function (object $entity, string $transition) use (&$log): void {
                $log[] = 'hook2';
            })
            ->transition('process', 'pending', 'processing')
            ->build();

        $order = new Order;
        $sm->apply($order, 'process');

        $this->assertSame(['hook1', 'hook2'], $log);
    }

    public function test_on_enter_hook_receives_entity_and_transition_name(): void
    {
        $receivedEntity = null;
        $receivedTransition = null;

        $sm = StateMachine::define()
            ->states(['pending', 'processing'])
            ->initial('pending')
            ->onEnter('processing', function (object $entity, string $transition) use (&$receivedEntity, &$receivedTransition): void {
                $receivedEntity = $entity;
                $receivedTransition = $transition;
            })
            ->transition('process', 'pending', 'processing')
            ->build();

        $order = new Order;
        $sm->apply($order, 'process');

        $this->assertSame($order, $receivedEntity);
        $this->assertSame('process', $receivedTransition);
    }
}
