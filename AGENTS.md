# AGENTS.md

Guidance for AI coding agents working in this repository. It covers three areas: **debugging**, **authorized bug bounty / security review**, and **CodeIgniter code**.

---

## 0. Rules that apply to everything

- **Detect the CodeIgniter version first** and say which one you found.
  - `app/` + `spark` + `writable/` = **CodeIgniter 4**
  - `application/` + `system/` + root `index.php` = **CodeIgniter 3**
  - Do not mix CI3 and CI4 APIs. Do not upgrade or migrate unless asked.
- **Plan before editing.** For anything bigger than a one-line fix, write a 3-6 step plan and wait for approval.
- **Small changes.** One logical change at a time. Show the diff and explain why.
- **Verify.** After each change, run the relevant test, route, or command and report the real output. Never write "this should work" without running something. If you can't run it, say so.
- **Don't invent.** If a function, config key, or library method isn't in this repo or the official docs, say you're not sure instead of making it up.
- **Ask before**: deleting files, changing the DB schema, running migrations, editing `.env` or credentials, installing packages, or running anything that contacts a remote server.
- **Never print, log, or commit secrets** (`.env`, API keys, tokens, DB passwords).
- **Keep replies short**: what you found, what you changed, how you verified it, what's left.

---

## 1. Debugging protocol

Follow these steps in order. Do not jump straight to a fix.

1. **Reproduce.** Get the exact URL or command, the input, and the full error message. If you can't reproduce it, say so and ask for logs.
2. **Read the evidence.** Read the error, stack trace, and logs before reading code. Quote the exact failing line.
3. **Hypothesize.** List 1-3 likely causes, ranked. For each, say what would confirm or rule it out.
4. **Test the top hypothesis** with the cheapest check (a log line, a query output, a failing test).
5. **Fix the root cause**, not the symptom. Don't hide errors with empty `try/catch`, don't suppress warnings, don't loosen validation just to make something pass.
6. **Verify** the fix and check neighboring code paths for regressions.
7. **Clean up.** Remove all temporary debug code (`var_dump`, `die`, extra logging). Add or update a test when practical.
8. **Summarize**: root cause, fix, how it was verified, anything still unconfirmed.

If two fixes in a row fail, stop. Re-read the evidence and revisit the hypotheses instead of stacking more changes.

### CodeIgniter debugging cheatsheet

| What | CI3 | CI4 |
|---|---|---|
| Logs | `application/logs/` | `writable/logs/` |
| Environment | `ENVIRONMENT` in `index.php` | `CI_ENVIRONMENT` in `.env` |
| Log a message | `log_message('error', $msg);` | `log_message('error', $msg);` |
| Last SQL query | `$this->db->last_query()` | `$this->db->getLastQuery()` |
| Profiler | `$this->output->enable_profiler(TRUE);` | Debug Toolbar (development only) |
| Inspect routes / run app | `php index.php controller method` | `php spark routes`, `php spark serve` |

### Common causes to check first

- **404, blank page, or "Class not found"**: wrong letter case in file or class names (Linux is case-sensitive, Windows/XAMPP is not), missing `.htaccess` or mod_rewrite, wrong `base_url`, route not defined, namespace mismatch (CI4).
- **500 error with no message**: `display_errors` is off. Read the PHP error log and the CI log. Compare the PHP version and required extensions (CI4 needs `intl`, `mbstring`, `json`, `curl`) with what the server has.
- **Config change broke everything**: syntax error. Run `php -l path/to/file.php`.
- **Database errors**: wrong credentials, driver, or charset; missing table or column; a migration that was never run.
- **Session or login problems**: session save path not writable, cookie domain or secure flag wrong, session driver misconfigured.
- **CSRF errors** ("The action you requested is not allowed" in CI3, 403 in CI4): token missing or expired on a form or AJAX request.
- **Works locally, fails on shared hosting**: folder permissions on `writable/` (CI4) or `application/cache` and `application/logs` (CI3), PHP version mismatch, missing extensions, document root not pointed at `public/` (CI4), `.env` not uploaded.

---

## 2. Bug bounty and security review

**Scope and authorization come first. Everything else is secondary.**

### Rules of engagement

- Only work on targets the user confirms are **in scope** of a program they are enrolled in, or on code and systems they **own** or have **written permission** to test. Ask for the program's scope and policy (or a link) before any testing. If scope is unclear, treat the target as out of scope and ask.
- **Non-destructive only.** No DoS or load testing, no mass scanning, no brute-forcing logins, no social engineering or phishing, no physical attacks, no touching third-party services outside scope.
- Follow the program's rules on automated tools and rate limits. Default to low-rate, manual testing.
- Use the user's **own test accounts and own data**. Don't read, change, or keep other users' data. If you reach sensitive data by accident, stop, don't copy it, and tell the user.
- A **proof of concept** is the smallest harmless demonstration of impact (for example, reading a second test account's record, or a benign alert payload). Do not build weaponized exploits, persistence, malware, or data-dumping scripts.
- Stop as soon as impact is clear. Don't pivot or chain into other systems beyond what's needed to prove the issue.
- **Don't overclaim.** Label each finding as `Confirmed`, `Likely`, or `Needs verification`. Never report something you haven't verified.
- Report privately through the program. Never disclose publicly.

### CodeIgniter source review checklist (highest priority first)

1. **Broken access control / IDOR**: endpoints that load records by ID without checking the current user owns them or has the right role. Routes with no auth filter (CI4 `Filters`) or controllers with no session check (CI3).
2. **SQL injection**: raw queries built by concatenation or interpolation; user input in `order_by`, `group_by`, column or table names, or string `where()`; `query()` without bindings.
3. **XSS**: output not escaped (`esc()` in CI4, `html_escape()` in CI3); user HTML rendered raw; user data inside inline scripts.
4. **CSRF**: protection disabled or excluded on state-changing routes; GET requests that change state.
5. **Authentication and sessions**: `md5`/`sha1` passwords; no `password_hash`; no session regeneration on login; weak or default `encryption_key`; guessable reset tokens; no rate limit on login or reset.
6. **File uploads**: no extension, MIME, or size validation; original filenames kept; files stored where they can be executed.
7. **Mass assignment (CI4)**: `$allowedFields` too broad or missing; passing `getPost()` straight into `insert()` or `update()`.
8. **Information exposure**: development mode in production; Debug Toolbar or profiler on; `.env`, `writable/`, or `application/logs` reachable from the web; verbose errors; backups or `.git` in the web root.
9. **Other classes**: open redirects (`redirect()` with user input), SSRF (server fetching user-supplied URLs), unsafe `unserialize()`, path traversal in file reads or includes.
10. **Misconfiguration**: CI4 auto-routing enabled, controllers exposed by default routes. Missing security headers only matter if they lead to real impact.

Skip low-value noise unless the program explicitly accepts it: missing headers with no impact, self-XSS, clickjacking on pages with no sensitive action, version disclosure alone.

### Report format

- **Title**
- **Severity** (with a short reason)
- **Affected asset / endpoint**
- **Summary**
- **Steps to reproduce** (numbered, with exact requests)
- **Impact** (what an attacker realistically gains)
- **Proof** (minimal)
- **Suggested fix** (CodeIgniter-specific code when possible)
- **References**

Keep it factual and short. No exaggeration.

---

## 3. CodeIgniter coding standards

### Structure and style

- Keep controllers thin. Put business logic in models, libraries, or services.
- **CI4**: use namespaces (`App\Controllers`, `App\Models`), PSR-12 style, type hints, and filenames that match class names. Use migrations for schema changes.
- **CI3**: controller and model class names start with a capital letter and match the filename (for example `User_model` in `User_model.php`). Don't add framework features that CI3 doesn't have.
- When you write code, give the **whole function or method**, not fragments, and state which CI version it targets.
- Follow the existing code style of the file you're editing.

### Database

Always use the query builder or bound parameters. Never concatenate user input into SQL.

```php
// CI4
$user = $builder->where('id', $id)->get()->getRow();
$db->query('SELECT * FROM users WHERE id = ?', [$id]);

// CI3
$user = $this->db->where('id', $id)->get('users')->row();
$this->db->query('SELECT * FROM users WHERE id = ?', array($id));
```

For anything user-controlled that goes in `order_by`, column names, or table names, check it against an allowlist.

### Input, validation, and output

- Validate every input on the server. Never trust client-side checks.
- Escape on output, not on input.

```php
// CI4 validation
$rules = ['email' => 'required|valid_email|max_length[254]'];
if (! $this->validate($rules)) {
    return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
}

// CI3 validation
$this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[254]');
if ($this->form_validation->run() === FALSE) {
    // reload the form with errors
}
```

```php
<?= esc($name) ?>          // CI4
<?= html_escape($name) ?>  // CI3
```

### Auth, sessions, and CSRF

- Hash passwords with `password_hash($pw, PASSWORD_DEFAULT)` and check with `password_verify()`. Never use `md5` or `sha1` for passwords.
- Regenerate the session ID on login and destroy the session on logout.
- Check authorization (role and ownership) in every controller method or filter, not only in the UI.
- Keep CSRF protection **on** (CI3: `$config['csrf_protection'] = TRUE;`; CI4: the `csrf` filter). Send the token with AJAX requests.
- CI4 models: set a minimal `$allowedFields`.

### Uploads

Validate extension, MIME type, and size. Rename the file to a random name. Store it outside the web root, or in a folder where PHP execution is disabled.

### Configuration and deployment

- Production: debug off (`ENVIRONMENT = production` in CI3, `CI_ENVIRONMENT = production` in CI4), `display_errors` off, errors written to logs only.
- Set a strong `encryption_key`. Keep secrets in `.env` (CI4) or outside the web root, and never commit them.
- CI4: point the web server's document root at `public/`. CI3: block direct web access to `application/` and `system/`.
- Don't leave backups, `.git`, `phpinfo()`, or test files in the web root.

### Testing

- CI4: write PHPUnit tests under `tests/` and run `vendor/bin/phpunit`.
- Add a regression test for every bug you fix when practical.
- Run `php -l` on every PHP file you change.