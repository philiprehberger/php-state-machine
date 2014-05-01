<?php

declare(strict_types=1);

namespace PhilipRehberger\StateMachine;

/**
 * Fluent builder for configuring guards and hooks on a single transition.
 */
final class TransitionBuilder
{
    /** @var array<int, callable> */
    private array $guards = [];

    /** @var array<int, callable> */
    private array $beforeHooks = [];

    /** @var array<int, callable> */
    private array $afterHooks = [];

    /**
     * Create a new transition builder.
     *
     * @param  string  $name  Transition identifier
     * @param  array<int, string>  $from  Valid source states
     * @param  string  $to  Target state
     * @param  StateMachineBuilder  $parent  Parent builder for method chaining
     */
    public function __construct(
        private readonly string $name,
        private readonly array $from,
        private readonly string $to,
        private readonly StateMachineBuilder $parent,
    ) {}

    /**
     * Add a guard callable that must return true for the transition to proceed.
     *
     * @param  callable(object): bool  $guard
     */
    public function guard(callable $guard): self
    {
        $this->guards[] = $guard;

        return $this;
    }

    /**
     * Add a hook to execute before the transition is applied.
     *
     * @param  callable(object): void  $hook
     */
    public function before(callable $hook): self
    {
        $this->beforeHooks[] = $hook;

        return $this;
    }

    /**
     * Add a hook to execute after the transition is applied.
     *
     * @param  callable(object): void  $hook
     */
    public function after(callable $hook): self
    {
        $this->afterHooks[] = $hook;

        return $this;
    }

    /**
     * Build the Transition value object from this builder's configuration.
     */
    public function buildTransition(): Transition
    {
        return new Transition(
            name: $this->name,
            from: $this->from,
            to: $this->to,
            guards: $this->guards,
            beforeHooks: $this->beforeHooks,
            afterHooks: $this->afterHooks,
        );
    }

    /**
     * Return to the parent StateMachineBuilder for continued chaining.
     *
     * @param  string|array<string>  $from
     */
    public function transition(string $name, string|array $from, string $to): TransitionBuilder
    {
        return $this->parent->transition($name, $from, $to);
    }

    /**
     * Build the state machine (delegates to the parent builder).
     */
    public function build(): StateMachine
    {
        return $this->parent->build();
    }
}
