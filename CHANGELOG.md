# Changelog

All notable changes to `php-state-machine` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.4] - 2026-03-20

### Added
- Expanded test suite with dedicated Transition, TransitionResult, and TransitionBuilder tests

## [1.0.3] - 2026-03-17

### Changed
- Standardized package metadata, README structure, and CI workflow per package guide

## [1.0.2] - 2026-03-16

### Changed
- Standardize composer.json: add type, homepage, scripts

## [1.0.1] - 2026-03-15

### Changed
- Standardize README badges

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
