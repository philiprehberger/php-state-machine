<?php

declare(strict_types=1);

namespace PhilipRehberger\StateMachine\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when an entity has a state that is not defined in the state machine.
 */
final class InvalidStateException extends InvalidArgumentException
{
    /**
     * Create a new exception for an invalid state.
     */
    public function __construct(string $state)
    {
        parent::__construct(
            "State '{$state}' is not a valid state in this state machine."
        );
    }
}
