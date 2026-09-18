# BookWagon: Information Assurance & Security 2 (IAS 2) Technical Specification
### Deep-Dive Analysis of the 17 Security Implementations
**Course:** Information Assurance and Security 2 (IAS 2)  
**Academic Project Document:** Module 3 — Security Architecture, Attack Models, Mitigations, and Implementation Details

---

## Executive Security Summary

The BookWagon system was designed under the principle of **Defense-in-Depth**, ensuring that no single point of failure compromises the confidentiality, integrity, or availability of the application. The system implements **17 dedicated security controls** spanning five primary domains:

| Domain | Controls Count | Primary Protection Focus |
| :--- | :---: | :--- |
| **I. Database & Intrusion Detection** | 3 | Zero SQLi via Prepared Statements, MariaDB IDS Triggers, Admin Threat Remediation |
| **II. Authentication & Identity Governance** | 6 | Bcrypt, Google TOTP 2FA, Email OTP, Recovery Codes, Lockouts, reCAPTCHA v2 |
| **III. Session & Access Control** | 3 | Session Fixation Defense, 10-Minute Idle Timeout, Role-Based Access Control (RBAC) |
| **IV. Application & Input Defense** | 3 | Secure File Upload Pipeline, XSS Output Encoding, CSRF / Method Protection |
| **V. Auditing, Forensics & Financial Integrity** | 2 | Immutable Forensic Audit Trail (`audit_logs`), Escrow & Double-Handshake Verification |

---

## Deep-Dive Analysis of the 17 Security Implementations

```
                               BOOKWAGON DEFENSE-IN-DEPTH MATRIX
 ┌─────────────────────────────────────────────────────────────────────────────────────────────┐
 │ 1. PRESENTATION TIER: Google reCAPTCHA v2 (#9) | XSS Output Encoding (#14)                  │
 ├─────────────────────────────────────────────────────────────────────────────────────────────┤
 │ 2. ACCESS & IDENTITY: Account Lockout (#8) | Bcrypt Hashing (#4) | Google TOTP 2FA (#5)    │
 │                       Email OTP (#6) | Cryptographic Recovery Codes (#7)                    │
 ├─────────────────────────────────────────────────────────────────────────────────────────────┤
 │ 3. SESSION & RBAC:   Session Fixation Defense (#10) | 10-Min Inactivity Timeout (#11)       │
 │                       Role-Based Access Control (#12) | Method / CSRF Checks (#15)          │
 ├─────────────────────────────────────────────────────────────────────────────────────────────┤
 │ 4. TRANSACTION LAYER: Escrow Hold & Settlement (#17) | Physical Meetup Handshake (#17)      │
 │                       Defense-in-Depth Upload Pipeline (#13)                                │
 ├─────────────────────────────────────────────────────────────────────────────────────────────┤
 │ 5. DATABASE & AUDIT:  100% Prepared Statements (#1) | Autonomous MariaDB IDS Triggers (#2)  │
 │                       Admin Incident Remediation (#3) | Immutable Audit Trail & Logs (#16)  │
 └─────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

### Domain I: Database & Intrusion Detection

#### 1. 100% Parameterized Prepared Statements (Zero SQL Injection)
* **Vulnerability Mitigated:** CWE-89 (SQL Injection)
* **Implementation Location:** Ubiquitous across all 133 PHP files (e.g., `login.php`, `book_details.php`, `cart.php`, `checkout.php`, `Admin/admin_kyc.php`).
* **Technical Mechanism:** Queries strictly separate SQL instruction syntax from external data inputs using MySQLi prepared statements:
  ```php
  $stmt = $conn->prepare("SELECT id, email, password, is_2fa_enabled FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  ```
  Dynamic string concatenation of user-controlled variables directly into SQL queries is strictly prohibited.

#### 2. Autonomous MariaDB Database Engine Triggers (Intrusion Detection System - IDS)
* **Vulnerability Mitigated:** Out-of-Band SQL Tampering / Unauthorized Direct Database Modification (phpMyAdmin / Direct Shell Access)
* **Implementation Location:** MariaDB Engine (`trg_audit_book_update`, `trg_audit_book_delete`, `trg_audit_user_update`).
* **Technical Mechanism:** Engine-level database triggers automatically intercept data changes on the `books` and `users` tables. If a book price is altered (e.g., ₱200 to ₱500) or a user wallet is modified out-of-band, the trigger autonomously writes a `RISK` audit record to `audit_logs`:
  ```sql
  CREATE TRIGGER trg_audit_book_update
  AFTER UPDATE ON books
  FOR EACH ROW
  BEGIN
      IF OLD.price <> NEW.price THEN
          INSERT INTO audit_logs (user_id, activity, details, status)
          VALUES (
              COALESCE(@app_current_user_id, 1),
              'UNAUTHORIZED_PRICE_MODIFICATION',
              CONCAT('Direct DB price change on Book #', NEW.id, ' from ', OLD.price, ' to ', NEW.price),
              'RISK'
          );
      END IF;
  END;
  ```

#### 3. Admin Threat Alert Banner & Incident Remediation ("SOLVE" Workflow)
* **Vulnerability Mitigated:** Incident Detection Lag / Lack of Forensic Incident Response
* **Implementation Location:** `admin_dashboard.php` and `Admin/audit_logs.php`.
* **Technical Mechanism:** When an autonomous trigger records a `RISK` entry, an urgent red alert banner is rendered on the administrator dashboard. Administrators can inspect the anomalous diff, document remediation steps in a modal, and click **"SOLVE"** to mark the entry `SOLVED` and clear the warning:
  ```php
  $updateStmt = $conn->prepare("UPDATE audit_logs SET status = 'SOLVED', resolution_notes = ? WHERE id = ?");
  $updateStmt->bind_param("si", $remediationNote, $logId);
  $updateStmt->execute();
  ```

---

### Domain II: Authentication & Identity Governance

#### 4. Bcrypt Cryptographic Password Storage
* **Vulnerability Mitigated:** CWE-256 (Unprotected Storage of Credentials), Rainbow Table Attacks
* **Implementation Location:** `signup.php`, `login.php`, `Admin/login_process.php`.
* **Technical Mechanism:** Passwords are cryptographically salted and hashed using PHP's native `password_hash()` with `PASSWORD_DEFAULT` (Bcrypt, minimum cost factor 10). Authentication performs constant-time comparisons via `password_verify($inputPassword, $storedHash)` to prevent timing side-channel attacks.

#### 5. Two-Factor Authentication (2FA) via Google Authenticator (TOTP)
* **Vulnerability Mitigated:** Credential Stuffing, Compromised Password Replay Attacks
* **Implementation Location:** `setup_2fa.php`, `verify_2fa.php`.
* **Technical Mechanism:** Conforms to RFC 6238 Time-Based One-Time Passwords (TOTP). Generates a base32 cryptographic secret, displays a QR code for Google Authenticator/Authy, and validates 6-digit codes within a 30-second epoch with clock-drift tolerances.

#### 6. Secondary Email OTP 2FA
* **Vulnerability Mitigated:** Authenticator Device Loss / Inaccessibility
* **Implementation Location:** `setup_2fa.php`, `verify_2fa.php`.
* **Technical Mechanism:** Delivers random 6-digit numeric verification tokens via email with a 10-minute time-to-live (TTL). Expired codes are automatically invalidated.

#### 7. Single-Use Cryptographic 2FA Backup/Recovery Codes
* **Vulnerability Mitigated:** Account Lockout Due to Lost Authentication Hardware
* **Implementation Location:** `verify_2fa.php`.
* **Technical Mechanism:** During 2FA setup, 5 high-entropy recovery codes (e.g. `XXXX-XXXX`) are generated and stored as a JSON array in `users.recovery_codes`. Upon successful redemption, the used code is purged immediately from the database to prevent reuse.

#### 8. Account Lockout & Brute-Force Rate Limiting
* **Vulnerability Mitigated:** CWE-307 (Improper Restriction of Excessive Authentication Attempts)
* **Implementation Location:** `login.php` (Lines 19–34).
* **Technical Mechanism:** Tracks failed login attempts in session and database counters. If 3 consecutive invalid credentials occur, an automatic 15-minute (900 seconds) lockout is activated. All subsequent attempts are rejected until the timer expires.

#### 9. Google reCAPTCHA v2 Bot Mitigation
* **Vulnerability Mitigated:** Automated Scraping, Dictionary Attacks, Sybil Account Creation
* **Implementation Location:** `login.php`, `signup.php`.
* **Technical Mechanism:** Requires human challenge validation on the client. The backend validates the challenge token against Google's verification endpoint (`https://www.google.com/recaptcha/api/siteverify`) before processing credentials.

---

### Domain III: Session & Access Control

#### 10. Session Fixation Defense
* **Vulnerability Mitigated:** CWE-384 (Session Fixation)
* **Implementation Location:** `login.php`, `verify_2fa.php`, `google_callback.php`, `Admin/login_process.php`.
* **Technical Mechanism:** The application immediately destroys the previous unauthenticated session token and generates a new cryptographically secure token using `session_regenerate_id(true)` upon successful credential verification.

#### 11. Inactivity Session Expiration (10-Minute Idle Auto-Logout)
* **Vulnerability Mitigated:** CWE-613 (Insufficient Session Expiration)
* **Implementation Location:** `guest_session.php` (Lines 27–35).
* **Technical Mechanism:** Compares the current timestamp against `$_SESSION['last_activity']`. If the idle duration exceeds 600 seconds (10 minutes), session data is destroyed via `session_unset()` and `session_destroy()`, and the user is redirected to the login page with a timeout notice.

#### 12. Role-Based Access Control (RBAC & IDOR Prevention)
* **Vulnerability Mitigated:** CWE-285 (Improper Authorization), CWE-639 (Insecure Direct Object References)
* **Implementation Location:** `Admin/admin_sidebar.php`, `include/seller_header.php`, `seller_dashboard.php`.
* **Technical Mechanism:** Enforces strict role compartmentalization (`guest`, `user`, `seller`, `admin`). Endpoints check session identities: unauthorized users attempting to access admin or seller endpoints are redirected and logged.

---

### Domain IV: Application & Input Protection

#### 13. Defense-in-Depth File Upload Security
* **Vulnerability Mitigated:** CWE-434 (Unrestricted Upload of File with Dangerous Type / Remote Code Execution)
* **Implementation Location:** `direct_upload.php`, `upload_helper.php`, `process_handover_condition.php`.
* **Technical Mechanism:**
  1. **True MIME Verification:** Inspects binary headers via `finfo_open(FILEINFO_MIME_TYPE)` rather than trusting client headers.
  2. **Extension Whitelisting:** Permitted extensions: `.jpg`, `.jpeg`, `.png`, `.webp`.
  3. **File Size Capping:** Strict 5MB limit per file.
  4. **Cryptographic Renaming:** Generates unguessable unique hashes (`bin2hex(random_bytes(6))` / `uniqid()`) to prevent direct execution or path traversal.
  5. **File Permissions:** Stored files are restricted to `0644`.

#### 14. Cross-Site Scripting (XSS) Prevention & Output Encoding
* **Vulnerability Mitigated:** CWE-79 (Improper Neutralization of Input During Web Page Generation)
* **Implementation Location:** Global application views (`forum_post.php`, `book_details.php`, `messages.php`).
* **Technical Mechanism:** User-generated content is sanitized using `filter_var()` and `strip_tags()` before storage, and consistently escaped with `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')` before rendering into HTML templates.

#### 15. CSRF & Form Request Method Validation
* **Vulnerability Mitigated:** CWE-352 (Cross-Site Request Forgery)
* **Implementation Location:** All state-changing action controllers (`ajax_handlers/`, `cart.php`, `checkout.php`).
* **Technical Mechanism:** Verifies `$_SERVER['REQUEST_METHOD'] === 'POST'`, checks session authentication, and sanitizes payload parameters before executing write operations.

---

### Domain V: Auditing, Forensics & Financial Integrity

#### 16. Immutable Forensic Audit Trail & Device Footprinting
* **Vulnerability Mitigated:** CWE-778 (Insufficient Logging)
* **Implementation Location:** `includes/audit_logger.php`, `login_history`, `audit_logs`.
* **Technical Mechanism:** Every security-sensitive transaction invokes `log_activity()`, recording the actor's user ID, remote IPv4/IPv6 address (`REMOTE_ADDR`), action category, detail description, and timestamp. In parallel, `login_history` tracks device User-Agent signatures.

#### 17. Escrow Financial Security & Double-Handshake Verification
* **Vulnerability Mitigated:** Financial Fraud, Rental Default, Condition Misrepresentation
* **Implementation Location:** `checkout.php`, `process_handover_condition.php`, `seller_confirm_handover.php`, `process_return_seller.php`.
* **Technical Mechanism:**
  1. **Escrow Hold:** Holds rental fees and refundable security deposits in the platform ledger.
  2. **Double-Handshake Verification:** Requires the seller to upload a timestamped condition photo (`initial_condition_photo`) and the buyer to scan a physical QR code upon receiving the book.
  3. **Dispute Arbitration:** Escrow funds remain locked until both parties agree on return condition or an administrator issues an arbitration decision via `Admin/admin_disputes.php`.
