# PHP State Machine

[![Tests](https://github.com/philiprehberger/php-state-machine/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/php-state-machine/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/php-state-machine.svg)](https://packagist.org/packages/philiprehberger/php-state-machine)
[![Last updated](https://img.shields.io/github/last-commit/philiprehberger/php-state-machine)](https://github.com/philiprehberger/php-state-machine/commits/main)

Declarative state machine with guards, hooks, and transition history.

## Requirements

- PHP 8.2+

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

### State entry/exit hooks

```php
$sm = StateMachine::define()
    ->states(['draft', 'review', 'published'])
    ->initial('draft')
    ->onEnter('review', fn (object $entity, string $transition) => $entity->log[] = "Entered review via $transition")
    ->onExit('draft', fn (object $entity, string $transition) => $entity->log[] = "Left draft via $transition")
    ->transition('submit', 'draft', 'review')
    ->transition('approve', 'review', 'published')
    ->build();
```

### Rollback the last transition

```php
$sm->apply($order, 'process');
$sm->rollback($order);
// $order->state === 'pending'
```

### Mermaid diagram export

```php
echo $sm->toMermaid();
// stateDiagram-v2
//     [*] --> pending
//     pending --> processing : process
//     processing --> shipped : ship
//     shipped --> delivered : deliver
//     pending --> cancelled : cancel
//     processing --> cancelled : cancel
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
| `$sm->rollback(object $entity)` | Revert the most recent transition |
| `$sm->toMermaid()` | Generate a Mermaid state diagram string |
| `$sm->history()` | Get the `TransitionHistory` instance |
| `$sm->initialState()` | Get the defined initial state |
| `$sm->states()` | Get all defined states |

### StateMachineBuilder

| Method | Description |
|--------|-------------|
| `->states(array $states)` | Define valid states |
| `->initial(string $state)` | Set the initial state |
| `->stateProperty(string $property)` | Set the entity property name (default: `'state'`) |
| `->onEnter(string $state, callable $hook)` | Register a hook that fires when entering a state |
| `->onExit(string $state, callable $hook)` | Register a hook that fires when leaving a state |
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
```

## Support

If you find this project useful:

⭐ [Star the repo](https://github.com/philiprehberger/php-state-machine)

🐛 [Report issues](https://github.com/philiprehberger/php-state-machine/issues?q=is%3Aissue+is%3Aopen+label%3Abug)

💡 [Suggest features](https://github.com/philiprehberger/php-state-machine/issues?q=is%3Aissue+is%3Aopen+label%3Aenhancement)

❤️ [Sponsor development](https://github.com/sponsors/philiprehberger)

🌐 [All Open Source Projects](https://philiprehberger.com/open-source-packages)

💻 [GitHub Profile](https://github.com/philiprehberger)

🔗 [LinkedIn Profile](https://www.linkedin.com/in/philiprehberger)

## License

[MIT](LICENSE)
