<?php

declare(strict_types=1);

namespace PhilipRehberger\StateMachine;

/**
 * Immutable value object representing a state machine transition.
 */
final readonly class Transition
{
    /**
     * Create a new transition definition.
     *
     * @param  string  $name  Transition identifier
     * @param  array<int, string>  $from  Valid source states
     * @param  string  $to  Target state
     * @param  array<int, callable(object, array<string, mixed>): bool>  $guards  Guard callables that must all return true
     * @param  array<int, callable(object, array<string, mixed>): void>  $beforeHooks  Hooks executed before the transition
     * @param  array<int, callable(object, array<string, mixed>): void>  $afterHooks  Hooks executed after the transition
     */
    public function __construct(
        public string $name,
        public array $from,
        public string $to,
        public array $guards = [],
        public array $beforeHooks = [],
        public array $afterHooks = [],
    ) {}
}
