# Changelog

All notable changes to `php-state-machine` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-03-15

### Added
- Initial release
- Fluent state machine builder with `StateMachine::define()`
- Transition guards to conditionally allow/block transitions
- Before and after hooks on transitions
- Transition history recording and retrieval
- `apply()`, `can()`, `allowedTransitions()`, `currentState()` API
- Configurable state property name
- `TransitionNotAllowedException` and `InvalidStateException`
