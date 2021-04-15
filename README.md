# PHP State Machine

[![Tests](https://github.com/philiprehberger/php-state-machine/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/php-state-machine/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/php-state-machine.svg)](https://packagist.org/packages/philiprehberger/php-state-machine)
[![License](https://img.shields.io/github/license/philiprehberger/php-state-machine)](LICENSE)

Declarative state machine with guards, hooks, and transition history.


## Requirements

| Dependency | Version |
|------------|---------|
| PHP        | ^8.2    |


## Installation

```bash
composer require philiprehberger/php-state-machine
```


## Usage

### Define a state machine

```php
use PhilipRehberger\StateMachine\StateMachine;

$sm = StateMachine::define()
    ->states(['pending', 'processing', 'shipped', 'delivered', 'cancelled'])
    ->initial('pending')
    ->stateProperty('state')
    ->transition('process', 'pending', 'processing')
    ->transition('ship', 'processing', 'shipped')
    ->transition('deliver', 'shipped', 'delivered')
    ->transition('cancel', ['pending', 'processing'], 'cancelled')
    ->build();
```

### Apply transitions

```php
$order = new Order(); // $order->state === 'pending'

$result = $sm->apply($order, 'process');
// $order->state === 'processing'
// $result->from === 'pending'
// $result->to === 'processing'
```

### Pass a payload to transitions

```php
$sm = StateMachine::define()
    ->states(['pending', 'approved', 'rejected'])
    ->initial('pending')
    ->transition('approve', 'pending', 'approved')
        ->guard(fn (object $order, array $payload) => ($payload['role'] ?? '') === 'manager')
        ->before(fn (object $order, array $payload) => $order->log[] = 'Approved by '.$payload['user'])
    ->transition('reject', 'pending', 'rejected')
    ->build();

$sm->apply($order, 'approve', ['role' => 'manager', 'user' => 'Alice']);
```

The `$payload` array is passed through to all guards, before hooks, and after hooks. It defaults to `[]` when omitted, so existing guards and hooks that accept only one parameter continue to work.

### Check if a transition is allowed

```php
$sm->can($order, 'ship');    // true
$sm->can($order, 'deliver'); // false
```

### Get available transitions

```php
$sm->allowedTransitions($order); // ['ship', 'cancel']
$sm->availableTransitions($order); // ['ship', 'cancel'] (alias)

// Payload is forwarded to guards when checking availability:
$sm->availableTransitions($order, ['role' => 'manager']);
```

### Guards

Guards are callables that must return `true` for the transition to proceed:

```php
$sm = StateMachine::define()
    ->states(['pending', 'processing', 'shipped'])
    ->initial('pending')
    ->transition('process', 'pending', 'processing')
        ->guard(fn (object $order, array $payload) => $order->isPaid)
    ->transition('ship', 'processing', 'shipped')
    ->build();
```

### Before and after hooks

```php
$sm = StateMachine::define()
    ->states(['pending', 'processing'])
    ->initial('pending')
    ->transition('process', 'pending', 'processing')
        ->before(fn (object $order, array $payload) => $order->log[] = 'Processing started')
        ->after(fn (object $order, array $payload) => $order->log[] = 'Processing complete')
    ->build();
```

### Transition history

```php
$sm->apply($order, 'process');
$sm->apply($order, 'ship');

$history = $sm->history();
$history->all();  // [TransitionResult, TransitionResult]
$history->last(); // TransitionResult { transition: 'ship', from: 'processing', to: 'shipped' }
```


## API

| Method | Description |
|--------|-------------|
| `StateMachine::define()` | Create a new `StateMachineBuilder` |
| `$sm->apply(object $entity, string $transition, array $payload = [])` | Apply a transition, returns `TransitionResult` |
| `$sm->can(object $entity, string $transition, array $payload = [])` | Check if a transition is allowed |
| `$sm->allowedTransitions(object $entity, array $payload = [])` | Get names of all allowed transitions |
| `$sm->availableTransitions(object $entity, array $payload = [])` | Alias for `allowedTransitions()` |
| `$sm->currentState(object $entity)` | Get the entity's current state |
| `$sm->history()` | Get the `TransitionHistory` instance |
| `$sm->initialState()` | Get the defined initial state |
| `$sm->states()` | Get all defined states |

### StateMachineBuilder

| Method | Description |
|--------|-------------|
| `->states(array $states)` | Define valid states |
| `->initial(string $state)` | Set the initial state |
| `->stateProperty(string $property)` | Set the entity property name (default: `'state'`) |
| `->transition(string $name, string\|array $from, string $to)` | Define a transition |
| `->build()` | Build the `StateMachine` |

### TransitionBuilder

| Method | Description |
|--------|-------------|
| `->guard(callable $guard)` | Add a guard `(object $entity, array $payload): bool` |
| `->before(callable $hook)` | Add a before-transition hook `(object $entity, array $payload): void` |
| `->after(callable $hook)` | Add an after-transition hook `(object $entity, array $payload): void` |


## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## License

MIT
