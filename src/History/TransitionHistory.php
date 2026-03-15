<?php

declare(strict_types=1);

namespace PhilipRehberger\StateMachine\History;

use PhilipRehberger\StateMachine\TransitionResult;

/**
 * Records and retrieves transition history for state machine operations.
 */
final class TransitionHistory
{
    /** @var array<int, TransitionResult> */
    private array $records = [];

    /**
     * Record a completed transition.
     */
    public function record(TransitionResult $result): void
    {
        $this->records[] = $result;
    }

    /**
     * Get all recorded transition results.
     *
     * @return array<int, TransitionResult>
     */
    public function all(): array
    {
        return $this->records;
    }

    /**
     * Get the last recorded transition result, or null if none exist.
     */
    public function last(): ?TransitionResult
    {
        if ($this->records === []) {
            return null;
        }

        return $this->records[array_key_last($this->records)];
    }
}
