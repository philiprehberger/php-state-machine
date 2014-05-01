<?php

declare(strict_types=1);

namespace PhilipRehberger\StateMachine;

/**
 * Immutable value object representing the result of a completed transition.
 */
final readonly class TransitionResult
{
    /**
     * Create a new transition result.
     *
     * @param  string  $transition  Name of the transition that was applied
     * @param  string  $from  State before the transition
     * @param  string  $to  State after the transition
     * @param  object  $entity  The entity that was transitioned
     */
    public function __construct(
        public string $transition,
        public string $from,
        public string $to,
        public object $entity,
    ) {}
}
