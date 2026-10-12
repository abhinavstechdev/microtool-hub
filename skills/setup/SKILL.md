---
name: setup
description: Onboarding and connection workflow for MicroTool Hub. Use when connecting ChatGPT to a user's workspace, verifying authentication, or checking workspace status.
---

# MicroTool Hub Onboarding & Connection Workflow

This skill guides ChatGPT when interacting with the **MicroTool Hub** remote MCP server for the first time or when verifying workspace connectivity.

## Verification Runbook

1. **Verify Authentication & Tools**:
   Check that MicroTool Hub MCP tools are available in the conversation context.
   If tools are unauthorized, guide the user to complete OAuth 2.1 authorization with their MicroTool Hub account.

2. **Check Workspace Status**:
   Call `list_projects` with `{ "limit": 5 }` to confirm read access to the user's workspace.

3. **Display Welcome & Available Actions**:
   Inform the user of their active projects and offer starter actions:
   - "Create a new micro web tool (e.g., calculator, converter, generator)"
   - "Inspect or modify an existing tool"
   - "Render an in-chat interactive preview"
   - "Publish an approved tool to the community catalog"
