<?php

declare(strict_types=1);

namespace PhilipRehberger\StateMachine\Tests;

use PhilipRehberger\StateMachine\StateMachine;
use PHPUnit\Framework\TestCase;

class MermaidTest extends TestCase
{
    public function test_to_mermaid_generates_state_diagram(): void
    {
        $sm = StateMachine::define()
            ->states(['draft', 'review', 'published'])
            ->initial('draft')
            ->transition('submit', 'draft', 'review')
            ->transition('approve', 'review', 'published')
            ->transition('reject', 'review', 'draft')
            ->build();

        $mermaid = $sm->toMermaid();

        $this->assertStringContainsString('stateDiagram-v2', $mermaid);
        $this->assertStringContainsString('[*] --> draft', $mermaid);
        $this->assertStringContainsString('draft --> review : submit', $mermaid);
        $this->assertStringContainsString('review --> published : approve', $mermaid);
        $this->assertStringContainsString('review --> draft : reject', $mermaid);
    }

    public function test_to_mermaid_expands_multiple_from_states(): void
    {
        $sm = StateMachine::define()
            ->states(['pending', 'processing', 'cancelled'])
            ->initial('pending')
            ->transition('process', 'pending', 'processing')
            ->transition('cancel', ['pending', 'processing'], 'cancelled')
            ->build();

        $mermaid = $sm->toMermaid();

        $this->assertStringContainsString('pending --> cancelled : cancel', $mermaid);
        $this->assertStringContainsString('processing --> cancelled : cancel', $mermaid);
    }

    public function test_to_mermaid_output_format(): void
    {
        $sm = StateMachine::define()
            ->states(['draft', 'review', 'published'])
            ->initial('draft')
            ->transition('submit', 'draft', 'review')
            ->transition('approve', 'review', 'published')
            ->transition('reject', 'review', 'draft')
            ->build();

        $expected = <<<'MERMAID'
stateDiagram-v2
    [*] --> draft
    draft --> review : submit
    review --> published : approve
    review --> draft : reject
MERMAID;

        $this->assertSame($expected, $sm->toMermaid());
    }
}
