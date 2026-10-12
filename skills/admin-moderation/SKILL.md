---
name: admin-moderation
description: Administrator workflow for reviewing, auditing, approving, or rejecting submitted micro-tools for the public community catalog. Use when an admin asks to inspect pending tools, audit source code with AI, or moderate submissions.
---

# Administrator Tool Moderation & AI Security Review Runbook

This skill teaches ChatGPT how to act as a senior platform auditor and security reviewer for **MicroTool Hub**, ensuring all community-published web tools are safe, responsive, client-side isolated, and functional before going public.

---

## Moderation Workflow Procedure

### Step 1: List Pending Submissions
Call `list_pending_tools` with `{ "limit": 20 }`:
- Retrieves tools submitted by creators that are currently in `pending_approval` status.
- Present a concise summary of pending submissions (Tool Name, Creator, Category, Submission Time, and Preview URL).

### Step 2: Code Retrieval & Automated Audit
For each tool under review, call `audit_tool_code` with `{ "project_id": "<project_id>" }`:
- Returns the complete source files (`index.html`, `style.css`, `app.js`) and automated safety checks:
  - `has_index_html`: Checks that entry point exists.
  - `outbound_network_detected`: Scans for unauthorized network telemetry (`fetch()`, `XMLHttpRequest`, `navigator.sendBeacon`, `WebSocket`).
  - `eval_detected`: Flags dynamic code evaluation.
  - `cookie_access_detected`: Flags `document.cookie` tampering.

### Step 3: Comprehensive AI Security & Quality Evaluation
Evaluate the returned source code against the MicroTool platform standards:
1. **Client-Side Sandbox Isolation (CRITICAL)**:
   - MicroTool Hub tools run locally in the user's browser without sending user inputs to third-party servers.
   - Any unannounced outbound network calls, data exfiltration, or external trackers MUST be rejected immediately.
2. **Dynamic Execution & Injection**:
   - Review any `eval()` or `Function()` calls. Legitimate math evaluators (e.g., scientific calculators) are acceptable with input sanitization; obfuscated scripts are not.
3. **Responsive UI & Quality**:
   - Verify `<meta name="viewport" content="width=device-width, initial-scale=1">` is present.
   - Verify styles look polished in both dark and light modes.
   - Verify interactive buttons have active event listeners and error handling.

### Step 4: Decision & Execution

#### Option A: Reject with Feedback
If the code fails security, sandbox, or usability requirements:
- Call `reject_tool` with:
  - `project_id`: ID, UUID, or slug of the tool.
  - `reason`: Polite, constructive feedback explaining exactly what the creator must fix before resubmitting.
- The reason will appear directly on the creator's developer dashboard.

#### Option B: Approve & Publish Live
If the tool passes all security and quality checks:
- Call `approve_tool` with:
  - `project_id`: ID, UUID, or slug of the tool.
  - `notes`: Optional approval notes.
- The tool status is immediately updated to `published` and `visibility = 'public'`, making it live in the community catalog.
- Report the public URL (`https://microtool-hub.getvoroa.com/tools/<slug>`) to the administrator.
