# System Flow Overview

This document describes the high-level flow of the AI Red-Teaming Arena.

## Flow Steps

1. A scenario is selected (e.g., prompt injection, PII leakage)
2. The Attacker Agent generates an adversarial prompt
3. The input is processed through guardrails (input filtering)
4. The LLM generates a response
5. The output is processed through guardrails (output filtering)
6. The Defender Agent evaluates the response
7. The system determines whether the output is safe or unsafe
8. Results are logged for evaluation and analysis

## Interaction Model

Attacker Agent → Guardrails (Input) → LLM → Guardrails (Output) → Defender Agent → Evaluation

## Purpose

The goal of this system is to:
- Simulate real-world adversarial attacks
- Evaluate AI safety mechanisms
- Provide measurable insights into model behavior
