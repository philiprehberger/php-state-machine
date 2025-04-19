<?php

declare(strict_types=1);

namespace PhilipRehberger\StateMachine\Tests;

use LogicException;
use PhilipRehberger\StateMachine\StateMachine;
use PhilipRehberger\StateMachine\Tests\Fixtures\Order;
use PHPUnit\Framework\TestCase;

class RollbackTest extends TestCase
{
    private function buildMachine(): StateMachine
    {
        return StateMachine::define()
            ->states(['pending', 'processing', 'shipped'])
            ->initial('pending')
            ->transition('process', 'pending', 'processing')
            ->transition('ship', 'processing', 'shipped')
            ->build();
    }

    public function test_rollback_reverts_to_previous_state(): void
    {
        $sm = $this->buildMachine();
        $order = new Order;

        $sm->apply($order, 'process');
        $this->assertSame('processing', $order->state);

        $result = $sm->rollback($order);

        $this->assertSame('pending', $order->state);
        $this->assertSame('processing', $result->from);
        $this->assertSame('pending', $result->to);
        $this->assertSame('rollback:process', $result->transition);
    }

    public function test_rollback_records_in_history(): void
    {
        $sm = $this->buildMachine();
        $order = new Order;

        $sm->apply($order, 'process');
        $sm->rollback($order);

        $history = $sm->history()->all();
        $this->assertCount(2, $history);
        $this->assertSame('rollback:process', $history[1]->transition);
    }

    public function test_rollback_throws_when_history_is_empty(): void
    {
        $sm = $this->buildMachine();
        $order = new Order;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot rollback: no transition history available.');
        $sm->rollback($order);
    }

    public function test_rollback_after_multiple_transitions(): void
    {
        $sm = $this->buildMachine();
        $order = new Order;

        $sm->apply($order, 'process');
        $sm->apply($order, 'ship');

        $result = $sm->rollback($order);

        $this->assertSame('processing', $order->state);
        $this->assertSame('shipped', $result->from);
        $this->assertSame('processing', $result->to);
    }
}
