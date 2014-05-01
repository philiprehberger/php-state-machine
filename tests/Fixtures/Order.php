<?php

declare(strict_types=1);

namespace PhilipRehberger\StateMachine\Tests\Fixtures;

/**
 * Simple order entity for testing state machine transitions.
 */
class Order
{
    public string $state = 'pending';

    public bool $isPaid = false;

    public bool $hasItems = true;

    /** @var array<int, string> */
    public array $log = [];
}
