---
name: packaging-tools-skill-design
type: Agent Skill
title: packaging-tools-skill-design
description: Detailed explanation of how agent skills are designed based on the Agent Skills and OKF specifications.
timestamp: 2026-06-30
---

# Skill Design and Specification

## Purpose
This sub-skill explains the architectural principles and design specifications for creating Agent Skills, ensuring they are portable, human-readable, and agent-friendly. It draws from the Agent Skills open standard and the Open Knowledge Format (OKF).

## Agent Skills Specification (agentskills.io)

The Agent Skills specification defines a lightweight format for extending AI agent capabilities.

### Directory Structure
A skill is a self-contained directory with the following structure:
- `SKILL.md` (Required): Metadata and primary instructions.
- `scripts/` (Optional): Executable code that agents can run.
- `references/` (Optional): Additional documentation like `REFERENCE.md` or `FORMS.md`.
- `assets/` (Optional): Static resources such as templates, images, or data files.

### Progressive Disclosure
To minimize context bloat, skills are loaded in stages:
1. **Metadata (~100 tokens)**: `name` and `description` from YAML frontmatter are loaded at startup for discovery.
2. **Instructions (< 5000 tokens)**: The full `SKILL.md` body is loaded only when the skill is activated by a matching task.
3. **Resources (on-demand)**: Files in `scripts/`, `references/`, or `assets/` are loaded only when explicitly required.

## Open Knowledge Format (OKF)

The Open Knowledge Format (OKF) is a minimal, vendor-neutral specification for representing curated context.

### Core Concepts
- **Knowledge Bundle**: A hierarchical collection of knowledge documents (the unit of distribution).
- **Concept**: A single Markdown document representing one unit of knowledge.
- **Concept ID**: The path of the file without the `.md` suffix (e.g., `tables/users`).
- **Frontmatter**: Every concept MUST have a YAML block at the top containing at least the `type` field.

### Synthesis Rule (Centralized Synthesis)
To prevent "index clutter," summaries of directory contents are stored in the parent directory. For any directory `D`, a file `D.md` must exist in its parent directory as its synthesis and navigation entry point.

## Design Best Practices

- **Conciseness**: Keep `SKILL.md` under 500 lines. Move details to `references/`.
- **Actionability**: Write instructions for agents, focusing on "what to do" and "how to do it."
- **Standardization**: Use standard Markdown and common YAML keys to ensure portability across different AI products.
- **Links**: Use absolute bundle-relative links or standard Markdown links to express relationships between concepts.
