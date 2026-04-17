# # AI Red-Teaming Arena: Multi-Agent Platform for LLM Safety Testing
A multi-agent system that simulates adversarial attacks and evaluates AI safety using guardrails, evaluation frameworks, and real-time observability.

## Overview
AI Red-Teaming Arena is a platform for systematically testing and hardening LLM-based systems using a multi-agent architecture. Autonomous attacker agents generate adversarial prompts, while defender agents apply guardrails and safety policies. The system evaluates outcomes, logs traces, and produces measurable metrics to improve model safety before deployment.

## Problem
Modern LLM applications are vulnerable to:
- Prompt injection and jailbreaks
- PII leakage and data exfiltration
- Toxic or harmful outputs
- Model policy violations

Existing workflows lack an interactive, repeatable, and measurable way to stress-test these systems prior to production.

## Solution
The platform simulates realistic adversarial behavior and systematically evaluates defense mechanisms in a controlled, measurable environment:
- Automated attack generation (single-turn and multi-turn)
- Programmable defenses via guardrails
- Centralized evaluation, logging, and metrics
- Interactive “duel” mode for demonstration and analysis

## Core Architecture

### 1) Scenario Layer
- Curated scenarios across categories: self-harm, hate, PII leakage, prompt injection
- Templates and seed prompts derived from established red-teaming patterns

### 2) Attack Engine (Red Team)
- Attacker agents generate adversarial prompts
- Strategies: prompt injection, jailbreaks, obfuscation, role-play, multi-turn refinement
- Coordinator selects strategies and iterates based on previous outcomes

### 3) Defense Engine (Blue Team)
- Guardrails layer:
  - Input filtering (jailbreak detection, PII masking)
  - Output filtering (moderation, policy enforcement)
- Defender agents:
  - Adjust prompts or guardrail profiles
  - Block or escalate unsafe outputs

### 4) Model Gateway
- Unified endpoint abstracting multiple LLM providers
- All traffic flows through:
  Attacker → Input Guardrails → Model → Output Guardrails → Evaluation
- Ensures provider-agnostic interaction with LLMs

### 5) Evaluation & Storage
- Store scenarios, prompts, responses, and decisions
- Label outcomes (safe/unsafe, blocked/allowed)
- Export runs for regression testing

### 6) Observability & UI
- Full trace per run (scenario, agent steps, guardrail decisions)
- Interactive duel view
- Analytics (attack success rate, defense effectiveness)

## System Flow
1. A scenario is selected
2. Attacker agent generates an adversarial prompt
3. Input passes through guardrails
4. Model generates a response
5. Output passes through guardrails
6. Defender agent evaluates and may intervene
7. Policy evaluation assigns the final verdict
8. All steps are logged and traced

## MVP Scope (Hackathon)
- Interactive duel mode with clear verdicts
- 5–10 predefined scenarios across key safety categories
- Integration with at least one guardrails toolkit
- Basic metrics dashboard (attack success rate, defense effectiveness)
- Trace logging for each run
- Designed to be deliverable within a 3-day hackathon scope

## Tech Stack

### Backend
- Laravel (latest) with Laravel AI SDK
- Python services (FastAPI) for agent orchestration if needed

### AI Providers
- OpenAI, Anthropic, Gemini, Groq, xAI (via unified abstraction)

### Guardrails
- NVIDIA NeMo Guardrails (primary)
- LLM Guard (auxiliary filtering)

### Red-Teaming
- DeepTeam (batch attack generation and reports)

### Evaluation
- Promptfoo (regression testing, rubric-based evaluation)

### Observability
- LangWatch with OpenTelemetry (tracing and experiment tracking)

### Database
- PostgreSQL or MySQL (scenarios, logs, metrics)

### Frontend
- Laravel Blade + React (duel UI and analytics)

### DevOps
- Docker or Laravel Sail for local development and deployment

## Evaluation Strategy
- Track per-scenario attack success rate
- Compare baseline vs. guarded configurations
- Use a rubric-based evaluation for nuanced safety judgments
- Maintain reproducible test suites for regression

## Deliverables (for submission)

- This repository (architecture, structure, and initial setup)
- Presentation (in /docs)
- Project description (this README)
- Initial agent stubs and system design

Note: The system is designed to be extended into a fully functional implementation, with ongoing development planned for complete agent workflows, integrations, and deployment.

## Value Proposition

- Enables organizations to identify vulnerabilities before deployment
- Reduces risk of unsafe or non-compliant AI behavior
- Provides measurable and repeatable safety evaluation workflows
- Bridges the gap between research-level red-teaming and practical implementation

## Future Work
- Expanded multi-turn adaptive attacks
- Multimodal red-teaming (image + text)
- Advanced analytics and reporting
- CI/CD integration for continuous safety testing

## Impact

This platform enables:
- Systematic and repeatable AI safety testing
- Automated adversarial simulation
- Measurable evaluation of model robustness

It provides a practical foundation for deploying safer AI systems in real-world environments.
