<?php

declare(strict_types=1);

namespace PhilipRehberger\StateMachine;

/**
 * Fluent builder for constructing a StateMachine instance.
 */
final class StateMachineBuilder
{
    /** @var array<int, string> */
    private array $states = [];

    private string $initial = '';

    private string $stateProperty = 'state';

    /** @var array<int, TransitionBuilder> */
    private array $transitionBuilders = [];

    /** @var array<string, array<int, callable>> */
    private array $enterHooks = [];

    /** @var array<string, array<int, callable>> */
    private array $exitHooks = [];

    /**
     * Define the valid states for the state machine.
     *
     * @param  array<int, string>  $states
     */
    public function states(array $states): self
    {
        $this->states = $states;

        return $this;
    }

    /**
     * Set the initial state for new entities.
     */
    public function initial(string $state): self
    {
        $this->initial = $state;

        return $this;
    }

    /**
     * Set the property name used to read/write state on entities.
     */
    public function stateProperty(string $property): self
    {
        $this->stateProperty = $property;

        return $this;
    }

    /**
     * Register a hook that fires whenever the state machine enters the given state.
     *
     * @param  callable(object, string): void  $hook  Receives the entity and transition name
     */
    public function onEnter(string $state, callable $hook): self
    {
        $this->enterHooks[$state][] = $hook;

        return $this;
    }

    /**
     * Register a hook that fires whenever the state machine exits the given state.
     *
     * @param  callable(object, string): void  $hook  Receives the entity and transition name
     */
    public function onExit(string $state, callable $hook): self
    {
        $this->exitHooks[$state][] = $hook;

        return $this;
    }

    /**
     * Define a transition with its source and target states.
     *
     * @param  string  $name  Transition identifier
     * @param  string|array<int, string>  $from  One or more valid source states
     * @param  string  $to  Target state
     */
    public function transition(string $name, string|array $from, string $to): TransitionBuilder
    {
        $fromArray = is_string($from) ? [$from] : $from;

        $builder = new TransitionBuilder($name, $fromArray, $to, $this);
        $this->transitionBuilders[] = $builder;

        return $builder;
    }

    /**
     * Build and return the configured StateMachine instance.
     */
    public function build(): StateMachine
    {
        $transitions = array_map(
            fn (TransitionBuilder $builder): Transition => $builder->buildTransition(),
            $this->transitionBuilders,
        );

        return new StateMachine(
            states: $this->states,
            initial: $this->initial,
            stateProperty: $this->stateProperty,
            transitions: $transitions,
            enterHooks: $this->enterHooks,
            exitHooks: $this->exitHooks,
        );
    }
}
