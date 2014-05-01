<?php

declare(strict_types=1);

namespace PhilipRehberger\StateMachine\Exceptions;

use RuntimeException;

/**
 * Thrown when a transition cannot be applied to the entity in its current state.
 */
final class TransitionNotAllowedException extends RuntimeException
{
    /**
     * Create a new exception for a disallowed transition.
     */
    public function __construct(string $transition, string $currentState)
    {
        parent::__construct(
            "Transition '{$transition}' is not allowed from state '{$currentState}'."
        );
    }
}
