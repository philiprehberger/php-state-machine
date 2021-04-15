<?php

declare(strict_types=1);

namespace PhilipRehberger\StateMachine;

use PhilipRehberger\StateMachine\Exceptions\InvalidStateException;
use PhilipRehberger\StateMachine\Exceptions\TransitionNotAllowedException;
use PhilipRehberger\StateMachine\History\TransitionHistory;

/**
 * Declarative state machine with guards, hooks, and transition history.
 */
final class StateMachine
{
    private readonly TransitionHistory $history;

    /**
     * Create a new state machine instance.
     *
     * @param  array<int, string>  $states  Valid states
     * @param  string  $initial  Initial state for new entities
     * @param  string  $stateProperty  Property name on entities that holds state
     * @param  array<int, Transition>  $transitions  Defined transitions
     */
    public function __construct(
        private readonly array $states,
        private readonly string $initial,
        private readonly string $stateProperty,
        private readonly array $transitions,
    ) {
        $this->history = new TransitionHistory;
    }

    /**
     * Create a new StateMachineBuilder for fluent configuration.
     */
    public static function define(): StateMachineBuilder
    {
        return new StateMachineBuilder;
    }

    /**
     * Apply a named transition to the given entity.
     *
     * @param  array<string, mixed>  $payload  Optional data passed to guards and hooks
     *
     * @throws TransitionNotAllowedException If the transition is not allowed from the current state
     * @throws InvalidStateException If the entity's current state is not valid
     */
    public function apply(object $entity, string $transition, array $payload = []): TransitionResult
    {
        $currentState = $this->currentState($entity);
        $transitionDef = $this->findTransition($transition, $currentState);

        if ($transitionDef === null) {
            throw new TransitionNotAllowedException($transition, $currentState);
        }

        if (! $this->passesGuards($transitionDef, $entity, $payload)) {
            throw new TransitionNotAllowedException($transition, $currentState);
        }

        foreach ($transitionDef->beforeHooks as $hook) {
            $hook($entity, $payload);
        }

        $this->setState($entity, $transitionDef->to);

        $result = new TransitionResult(
            transition: $transition,
            from: $currentState,
            to: $transitionDef->to,
            entity: $entity,
        );

        $this->history->record($result);

        foreach ($transitionDef->afterHooks as $hook) {
            $hook($entity, $payload);
        }

        return $result;
    }

    /**
     * Check whether a named transition can be applied to the entity.
     *
     * @param  array<string, mixed>  $payload  Optional data passed to guards
     */
    public function can(object $entity, string $transition, array $payload = []): bool
    {
        $currentState = $this->currentState($entity);
        $transitionDef = $this->findTransition($transition, $currentState);

        if ($transitionDef === null) {
            return false;
        }

        return $this->passesGuards($transitionDef, $entity, $payload);
    }

    /**
     * Get all transition names that are currently allowed for the entity.
     *
     * @param  array<string, mixed>  $payload  Optional data passed to guards
     * @return array<int, string>
     */
    public function allowedTransitions(object $entity, array $payload = []): array
    {
        $currentState = $this->currentState($entity);
        $allowed = [];

        foreach ($this->transitions as $transition) {
            if (in_array($currentState, $transition->from, true) && $this->passesGuards($transition, $entity, $payload)) {
                $allowed[] = $transition->name;
            }
        }

        return $allowed;
    }

    /**
     * Get all transition names that are currently available for the entity.
     *
     * Alias for `allowedTransitions()`.
     *
     * @param  array<string, mixed>  $payload  Optional data passed to guards
     * @return array<int, string>
     */
    public function availableTransitions(object $entity, array $payload = []): array
    {
        return $this->allowedTransitions($entity, $payload);
    }

    /**
     * Get the current state of the entity.
     *
     * @throws InvalidStateException If the entity's state is not in the defined states
     */
    public function currentState(object $entity): string
    {
        $property = $this->stateProperty;
        $state = $entity->{$property};

        if (! in_array($state, $this->states, true)) {
            throw new InvalidStateException($state);
        }

        return $state;
    }

    /**
     * Get the transition history recorder.
     */
    public function history(): TransitionHistory
    {
        return $this->history;
    }

    /**
     * Get the initial state defined for this state machine.
     */
    public function initialState(): string
    {
        return $this->initial;
    }

    /**
     * Get all defined states.
     *
     * @return array<int, string>
     */
    public function states(): array
    {
        return $this->states;
    }

    /**
     * Find a matching transition definition by name and current state.
     */
    private function findTransition(string $name, string $currentState): ?Transition
    {
        foreach ($this->transitions as $transition) {
            if ($transition->name === $name && in_array($currentState, $transition->from, true)) {
                return $transition;
            }
        }

        return null;
    }

    /**
     * Check whether all guards pass for the given transition and entity.
     *
     * @param  array<string, mixed>  $payload
     */
    private function passesGuards(Transition $transition, object $entity, array $payload = []): bool
    {
        foreach ($transition->guards as $guard) {
            if (! $guard($entity, $payload)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set the state property on the entity.
     */
    private function setState(object $entity, string $state): void
    {
        $property = $this->stateProperty;
        $entity->{$property} = $state;
    }
}
