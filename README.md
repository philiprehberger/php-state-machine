# PHP State Machine

[![Tests](https://github.com/philiprehberger/php-state-machine/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/php-state-machine/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/php-state-machine.svg)](https://packagist.org/packages/philiprehberger/php-state-machine)
[![License](https://img.shields.io/github/license/philiprehberger/php-state-machine)](LICENSE)

Declarative state machine with guards, hooks, and transition history. Framework-agnostic, zero external dependencies.

---

## Requirements

| Dependency | Version |
|------------|---------|
| PHP        | ^8.2    |

---

## Installation

```bash
composer require philiprehberger/php-state-machine
```

---

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

### Check if a transition is allowed

```php
$sm->can($order, 'ship');    // true
$sm->can($order, 'deliver'); // false
```

### Get allowed transitions

```php
$sm->allowedTransitions($order); // ['ship', 'cancel']
```

### Guards

Guards are callables that must return `true` for the transition to proceed:

```php
$sm = StateMachine::define()
    ->states(['pending', 'processing', 'shipped'])
    ->initial('pending')
    ->transition('process', 'pending', 'processing')
        ->guard(fn (object $order) => $order->isPaid)
    ->transition('ship', 'processing', 'shipped')
    ->build();
```

### Before and after hooks

```php
$sm = StateMachine::define()
    ->states(['pending', 'processing'])
    ->initial('pending')
    ->transition('process', 'pending', 'processing')
        ->before(fn (object $order) => $order->log[] = 'Processing started')
        ->after(fn (object $order) => $order->log[] = 'Processing complete')
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

---

## API

| Method | Description |
|--------|-------------|
| `StateMachine::define()` | Create a new `StateMachineBuilder` |
| `$sm->apply(object $entity, string $transition)` | Apply a transition, returns `TransitionResult` |
| `$sm->can(object $entity, string $transition)` | Check if a transition is allowed |
| `$sm->allowedTransitions(object $entity)` | Get names of all allowed transitions |
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
| `->guard(callable $guard)` | Add a guard (must return `true` to allow) |
| `->before(callable $hook)` | Add a before-transition hook |
| `->after(callable $hook)` | Add an after-transition hook |

---

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## License

MIT
