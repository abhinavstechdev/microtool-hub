---
name: create-microtool
description: Standard operational workflow for creating, coding, validating, snapshotting, and previewing web micro-tools in MicroTool Hub.
---

# Micro-Tool Generation & Verification Runbook

Follow this strict step-by-step procedure whenever the user asks to build, scaffold, or generate a micro web tool.

## Execution Procedure

### Step 1: Initialize Project
Call `create_project`:
- Provide a clean `name` (e.g. "JSON Formatter & Validator").
- Provide a URL-friendly `slug` (e.g. "json-formatter").
- Provide a concise `description` and initial `category` (e.g. "Developer Tools", "Utilities", "Calculators").

### Step 2: Write Source Files
Every web micro-tool should be fully self-contained across standard web assets.
Call `write_project_file` for each necessary file:
1. `index.html`: Clean HTML5 markup, semantic containers, accessible form elements, responsive layout.
2. `style.css`: Polished CSS styling with dark/light theme support, responsive typography, and clean button states.
3. `app.js`: Client-side logic, input validation, interactive calculations/copying/downloading, and error handling.

### Step 3: Verify File Structure
Call `list_project_files` with `{ "project_id": "<project_id>" }` to verify all written files exist with valid sizes.

### Step 4: Validate Code Syntax & Limits
Call `validate_project` with `{ "project_id": "<project_id>" }`:
- Check for tag closures, script references, and file quota limits.
- If any syntax or structural errors are flagged, fix them using `write_project_file`.

### Step 5: Create Milestone Snapshot
Call `create_snapshot`:
- Set `version_tag` to `"v1.0.0-draft"`.
- Set `label` to `"Initial implementation"`.

### Step 6: Present In-Chat Interactive Preview
Call `render_microtool_widget`:
- Pass `{ "project_id": "<project_id>" }`.
- This causes ChatGPT to render the interactive MCP Apps widget inside the chat, allowing the user to test the micro-tool immediately.

### Step 7: Report & Next Steps
Summarize the created tool for the user:
- Provide the workspace link and preview status.
- Explain the features implemented.
- Remind the user that they can ask for edits or publish the tool to the community via `publish_project`.
