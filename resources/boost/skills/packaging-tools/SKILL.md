---
name: packaging-tools
type: Agent Skill
title: packaging-tools
description: Tools to simplify publishing github packages
timestamp: 2026-06-30
---

# Packaging Tools

## Purpose
This skill provides a set of tools to simplify publishing GitHub packages, including badge generation, AI guidelines, migration management, markdown assembly, setup, and speed seeding.

## When to Use
Use when developing Laravel packages and wanting to automate documentation, badges, migrations, or testing setup.

## Sub-skills

| Skill | Description |
|---|---|
| [packaging-tools-badges](sub-skills/packaging-tools-badges.md) | Generate SVG badges for project metrics |
| [packaging-tools-guidelines](sub-skills/packaging-tools-guidelines.md) | Write AI guidelines and skills for projects based on Laravel Boost standards |
| [packaging-tools-imported-migrations](sub-skills/packaging-tools-imported-migrations.md) | Regenerate package migrations from a live database schema |
| [packaging-tools-markdown-assembly](sub-skills/packaging-tools-markdown-assembly.md) | Assemble modular documentation and class references into a README |
| [packaging-tools-setup](sub-skills/packaging-tools-setup.md) | Install, configure, and run packaging tools via composer scripts |
| [packaging-tools-skill-design](sub-skills/packaging-tools-skill-design.md) | Detailed explanation of how agent skills are designed based on the Agent Skills and OKF specifications |
| [packaging-tools-speed-seeding](sub-skills/packaging-tools-speed-seeding.md) | Speed up tests by loading a pre-generated SQL dump instead of running migrations |
