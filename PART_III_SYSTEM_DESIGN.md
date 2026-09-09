# PART III – SYSTEM DESIGN
### BookWagon: A Secure Web-Based Platform for Sustainable Book Rental and Resale
**Course:** Information Assurance and Security 2 (IAS 2)  
**Document Module:** System Architecture, Use Case Specifications, Database Design (ERD), and System Flowcharts

---

## A. System Architecture

The BookWagon system implements a **Defense-in-Depth, Multi-Tier Secure Web Architecture**. Rather than allowing direct communication between user requests and database records, every transaction and data request must cross a dedicated **Authentication and Security Enforcement Layer**.

```mermaid
graph TD
    subgraph Client_Tier["1. CLIENT / PRESENTATION TIER"]
        UserBrowser["User Web Browser (Desktop / Mobile)"]
        UIElements["Responsive Interface (HTML5, Vanilla CSS, Inter Typography, JS, Bootstrap 5)"]
        ClientValidation["Client-Side Form Validation & Google reCAPTCHA v2 Widget"]
    end

    subgraph Web_App_Tier["2. WEB APPLICATION TIER (XAMPP / Apache / PHP 8.2)"]
        WebServer["Apache HTTP Server"]
        RoutingEngine["Controller / Page Handlers (PHP Scripts)"]
        SessionStore["PHP Session Management (session.php / session_start())"]
    end

    subgraph Security_Layer["3. AUTHENTICATION & SECURITY LAYER"]
        CaptchaVerifier["Google reCAPTCHA Verification"]
        BruteForceLockout["Brute-Force Guard (3-Attempt Lockout Tracker)"]
        PasswordVerifier["Password Security (password_verify() / Bcrypt)"]
        TwoFactorEngine["2FA Engine (Google Authenticator TOTP)"]
        RBACGuard["Role-Based Access Control (RBAC & Authorization Guard)"]
        InputSanitizer["Input Sanitization & Parameter Validation"]
        UploadSecurity["Secure File Upload Validator (MIME, Extension, Size)"]
        SQLDefenses["SQL Injection Prevention (MySQLi Prepared Statements)"]
        AuditLogger["Audit Trail & Security Event Logger"]
    end

    subgraph Data_Tier["4. SECURE DATABASE TIER (MySQL / MariaDB)"]
        DBInstance[("bookwagon_db")]
        UserTables["Identity & Security Tables (users, roles, audit_logs)"]
        CatalogTables["Commerce Tables (books, book_images, cart, orders, rentals, returns)"]
    end

    UserBrowser -->|HTTPS Request + User Input| WebServer
    WebServer --> RoutingEngine
    RoutingEngine --> CaptchaVerifier
    CaptchaVerifier -->|Passed| BruteForceLockout
    BruteForceLockout -->|Not Locked| PasswordVerifier
    PasswordVerifier -->|Valid Hash| TwoFactorEngine
    TwoFactorEngine -->|Valid TOTP| SessionStore
    SessionStore --> RBACGuard
    RBACGuard --> InputSanitizer
    InputSanitizer --> UploadSecurity
    UploadSecurity --> SQLDefenses
    SQLDefenses -->|Parameterized Query| DBInstance
    DBInstance --> UserTables
    DBInstance --> CatalogTables
    
    %% Audit Logging connections
    PasswordVerifier -.->|Log Failure/Success| AuditLogger
    BruteForceLockout -.->|Log Lockout Event| AuditLogger
    RBACGuard -.->|Log Unauthorized Access| AuditLogger
    SQLDefenses -.->|Log Critical Changes| AuditLogger
    AuditLogger -->|INSERT log| DBInstance
```

### Architectural Layer Breakdown

1. **Client / Presentation Tier:**
   * Runs inside the client’s web browser.
   * Delivers an intuitive responsive interface using semantic HTML5, custom Vanilla CSS, and Bootstrap 5 components.
   * Performs real-time client-side checks (password confirmation, file format checks) and embeds Google reCAPTCHA v2 to stop bot submissions before reaching server bandwidth.

2. **Web Application Tier (Apache & PHP 8.2):**
   * Hosted on an Apache HTTP server (XAMPP environment).
   * Orchestrates application logic across page controllers (`login.php`, `book_details.php`, `cart.php`, `Manage_books.php`, `rental_request.php`, `history.php`, etc.).
   * Maintains user state using hardened PHP Sessions (`session_regenerate_id(true)` upon privilege changes; session guards in `session.php`).

3. **Authentication & Security Enforcement Layer (The Core IAS Layer):**
   * **Bot Mitigation:** Validates reCAPTCHA tokens against Google’s verification API.
   * **Brute-Force Protection:** Enforces a 3-consecutive-failure account lockout window.
   * **Cryptographic Defense:** Uses standard PHP `password_hash()` with `PASSWORD_BCRYPT` (cost factor 10+) and constant-time verification with `password_verify()`.
   * **Two-Factor Authentication (2FA):** Verifies 6-digit Time-based One-Time Passwords (TOTP) from Google Authenticator (`verify_2fa.php`).
   * **Role-Based Access Control (RBAC):** Restricts endpoints by verifying `$_SESSION['usertype']` (Guest, Registered User, Verified Seller, Administrator). Direct URL tampering is prevented.
   * **SQL Injection Defense:** All queries utilize parameterized queries (`prepare()`, `bind_param()`, `execute()`). Direct string concatenation is prohibited.
   * **Secure Upload Pipeline:** Validates book covers, condition photos, and identification proofs through file size caps (max 5MB), MIME type inspection (`image/jpeg`, `image/png`, `image/webp`), and randomized filenames (`uniqid()`).
   * **Audit Logging:** Logs timestamp, user ID, IP address, user-agent, action type, and status to an immutable `audit_logs` table.

4. **Database Tier (MySQL / MariaDB):**
   * Relational database engine storing persistent entities under `bookwagon_db`.
   * Enforces referential integrity through foreign keys with cascading rules.

---

## B. Use Case Diagram

The Use Case model defines the functional interactions between the four primary actors (**Guest**, **Registered User / Buyer & Renter**, **Verified Seller**, and **Administrator**) and the **BookWagon System Boundary**.

```mermaid
flowchart LR
    %% Actors
    Guest((Guest))
    User((Registered User))
    Seller((Verified Seller))
    Admin((Administrator))

    subgraph SystemBoundary["BookWagon Secure Information System"]
        %% Guest Use Cases
        UC1[Browse Public Catalog]
        UC2[Search Books by Title / Genre]
        UC3[Register Account with reCAPTCHA]
        UC4[Authenticate Login with Password & 2FA]

        %% User Use Cases
        UC5[Manage Profile & Security Settings]
        UC6[Configure 2FA Authenticator]
        UC7[View Book Condition & Photos]
        UC8[Add Book to Cart]
        UC9[Rent Book with Duration Selection]
        UC10[Buy Book Whole]
        UC11[Create / Propose Book Swap]
        UC12[Submit Seller Verification Application]
        UC13[Track Orders & Rental Returns]

        %% Seller Use Cases
        UC14[Manage Book Listings]
        UC15[Upload Multi-Photo Condition Documentation]
        UC16[Configure Pricing & Rental Durations]
        UC17[Fulfill Orders & Update Status]
        UC18_S[Receive & Inspect Book Returns]

        %% Admin Use Cases
        UC18[Manage User Accounts & Roles]
        UC19[Review & Approve Seller IDs]
        UC20[Monitor System Audit Logs]
        UC21[Enforce Account Lockouts & Security Overrides]

        %% System Security Background Actions
        SEC1[Validate Credentials & Lockout Status]
        SEC2[Record Action in Audit Log]
    end

    %% Guest Connections
    Guest --> UC1
    Guest --> UC2
    Guest --> UC3
    Guest --> UC4

    %% User Connections (inherits guest)
    User --> UC1
    User --> UC4
    User --> UC5
    User --> UC6
    User --> UC7
    User --> UC8
    User --> UC9
    User --> UC10
    User --> UC11
    User --> UC12
    User --> UC13

    %% Seller Connections (inherits user)
    Seller --> UC14
    Seller --> UC15
    Seller --> UC16
    Seller --> UC17
    Seller --> UC18_S

    %% Admin Connections
    Admin --> UC4
    Admin --> UC18
    Admin --> UC19
    Admin --> UC20
    Admin --> UC21

    %% Includes & Security Extensions
    UC3 -.->|<<include>>| SEC2
    UC4 -.->|<<include>>| SEC1
    UC4 -.->|<<include>>| SEC2
    UC12 -.->|<<include>>| SEC2
    UC15 -.->|<<include>>| SEC2
    UC18 -.->|<<include>>| SEC2
    UC19 -.->|<<include>>| SEC2
    UC21 -.->|<<include>>| SEC2
```

### Use Case Specification Matrix

| Use Case ID | Use Case Name | Primary Actor(s) | Pre-Conditions | Security Controls Applied |
| :--- | :--- | :--- | :--- | :--- |
| **UC-01** | **User Registration** | Guest | Guest is not logged in. | Google reCAPTCHA v2 validation, email format regex, server-side duplicate check, bcrypt password hashing. |
| **UC-02** | **Secure Login** | Guest, User, Seller, Admin | Account exists and is not locked. | Brute-force lockout check (max 3 failed tries), `password_verify()`, TOTP 2FA prompt, session fixation mitigation (`session_regenerate_id`). |
| **UC-03** | **Book Rental & Purchase** | Registered User | Authenticated with active session. | Role verification (`user`), stock check, input validation on duration (`rental_weeks`), CSRF-safe POST submission. |
| **UC-04** | **Condition Photo Documentation** | Verified Seller | Authenticated with `seller` role. | Role-Based Authorization Guard, MIME type inspection, file extension whitelist (`jpg, png, webp`), 5MB size limit. |
| **UC-05** | **Seller Verification Review** | Administrator | Authenticated with `admin` role. | Strict admin session check (`usertype === 'admin'`), audit trail recording of approval/rejection decision. |
| **UC-06** | **Security Audit Log Monitoring** | Administrator | Authenticated with `admin` role. | Read-only parameterized query, administrative authorization check, sanitized output rendering (XSS prevention). |

---

## C. Database Design

### 1. Entity Relationship Diagram (ERD)

The database design incorporates required security tables (`users`, `roles`, `audit_logs`) alongside application domain tables (`sellers`, `books`, `book_images`, `cart`, `orders`, `order_items`, `book_rentals`, `book_returns`, `book_swaps`, and `notifications`).

```mermaid
erDiagram
    ROLES ||--o{ USERS : "assigned to"
    USERS ||--o{ AUDIT_LOGS : "generates"
    USERS ||--o| SELLERS : "applies as"
    USERS ||--o{ BOOKS : "owns / lists"
    USERS ||--o{ CART : "manages"
    USERS ||--o{ ORDERS : "places"
    USERS ||--o{ BOOK_RENTALS : "rents"
    USERS ||--o{ BOOK_RETURNS : "initiates"
    USERS ||--o{ BOOK_SWAPS : "proposes"
    USERS ||--o{ NOTIFICATIONS : "receives"

    SELLERS ||--o{ BOOKS : "stocks"
    SELLERS ||--o{ BOOK_RENTALS : "lends"
    SELLERS ||--o{ BOOK_RETURNS : "inspects / receives"

    BOOKS ||--o{ BOOK_IMAGES : "documented by"
    BOOKS ||--o{ CART : "added into"
    BOOKS ||--o{ ORDER_ITEMS : "ordered in"
    BOOKS ||--o{ BOOK_RENTALS : "leased in"
    BOOKS ||--o{ BOOK_RETURNS : "returned in"
    BOOKS ||--o{ BOOK_SWAPS : "offered / requested"

    ORDERS ||--o{ ORDER_ITEMS : "contains"
    ORDERS ||--o{ BOOK_RENTALS : "originates"
    BOOK_RENTALS ||--o{ BOOK_RETURNS : "linked to"

    ROLES {
        int role_id PK
        varchar role_name UK "admin, seller, user, guest"
        varchar description
        text permissions
    }

    USERS {
        int id PK
        int role_id FK
        varchar username UK
        varchar email UK
        varchar password "Bcrypt hash"
        varchar firstname
        varchar lastname
        varchar usertype "Legacy role sync"
        tinyint two_factor_enabled "0 or 1"
        varchar two_factor_secret "TOTP secret key"
        int failed_attempts "Brute force counter"
        datetime lockout_until "Lockout timestamp"
        int login_count
        timestamp created_at
        timestamp updated_at
    }

    AUDIT_LOGS {
        int log_id PK
        int user_id FK
        varchar action "LOGIN, LOGOUT, FAILED_LOGIN, LOCKOUT, UPDATE, DELETE"
        varchar ip_address "Client IP"
        varchar user_agent "Client browser"
        text details "Contextual audit payload"
        timestamp created_at
    }

    SELLERS {
        int id PK
        int user_id FK
        varchar shop_name
        varchar business_email
        varchar business_phone
        text address
        enum status "pending, approved, rejected"
        varchar shop_logo
        timestamp created_at
    }

    BOOKS {
        int book_id PK
        int user_id FK "Seller / Owner"
        varchar title
        varchar author
        varchar ISBN
        varchar genre
        varchar theme
        enum book_type "Paperback, Hardcover, E-book, Audiobook"
        enum condition "New, Like New, Very Good, Good, Fair, Poor"
        text damages
        enum listing_type "both, rent, sale"
        decimal price "Sale price"
        decimal rent_price "Weekly rental rate"
        decimal security_deposit "Refundable deposit"
        int stock
        varchar cover_image
        text seller_note
        timestamp created_at
    }

    BOOK_IMAGES {
        int image_id PK
        int book_id FK
        varchar image_url
        enum image_type "front, back, spine, pages, damage, other"
        timestamp created_at
    }

    CART {
        int cart_id PK
        int user_id FK
        int book_id FK
        int quantity
        enum purchase_type "buy, rent"
        int rental_weeks "1 to 16 weeks"
        timestamp created_at
    }

    ORDERS {
        int order_id PK
        int user_id FK
        varchar first_name
        varchar last_name
        varchar email
        varchar phone
        text address
        varchar payment_method "cod, pickup, qrph"
        varchar payment_status "pending, paid, refunded"
        enum order_status "pending, processing, shipped, delivered, cancelled"
        decimal total_amount
        timestamp order_date
    }

    ORDER_ITEMS {
        int item_id PK
        int order_id FK
        int book_id FK
        int quantity
        decimal price
        enum purchase_type "buy, rent"
        int rental_weeks
    }

    BOOK_RENTALS {
        int rental_id PK
        int order_id FK
        int user_id FK "Renter"
        int book_id FK
        int seller_id FK
        int rental_weeks
        datetime rental_date
        datetime due_date "Expected return date"
        datetime return_date "Actual return date"
        decimal total_price
        decimal late_fee
        enum status "active, returned, overdue, cancelled"
    }

    BOOK_RETURNS {
        int return_id PK
        int rental_id FK
        int user_id FK "Renter"
        int seller_id FK "Seller / Owner"
        int book_id FK
        varchar return_method "dropoff, pickup"
        text return_details "JSON dropoff/pickup notes"
        enum status "pending, received, completed, cancelled"
        datetime request_date
        datetime received_date
        datetime completed_date
        enum book_condition "excellent, good, fair, damaged"
        decimal late_fee
        decimal damage_fee
        decimal additional_fee
    }

    BOOK_SWAPS {
        int swap_id PK
        int requester_id FK
        int requested_book_id FK
        int offered_book_id FK
        enum status "pending, accepted, rejected, completed"
        timestamp created_at
    }

    NOTIFICATIONS {
        int id PK
        int user_id FK
        int sender_id FK
        varchar type
        text content
        tinyint is_read
        timestamp created_at
    }
```

---

### 2. Data Dictionary (Security & Core Tables)

#### Table: `users`
*Stores user identities, cryptographic credentials, 2FA parameters, and lockout state.*

| Column Name | Data Type | Nullable | Key | Default | Security & Operational Description |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | `INT(11)` | No | PK, AI | Auto | Unique identifier for the user account. |
| `role_id` | `INT(11)` | Yes | FK | 3 (`user`) | References `roles(role_id)` for RBAC enforcement. |
| `username` | `VARCHAR(50)` | No | UK | None | Sanitized unique username for identification. |
| `email` | `VARCHAR(255)` | No | UK | None | Unique email address used as primary login credential. |
| `password` | `VARCHAR(255)` | No | None | None | **Bcrypt password hash** generated via `password_hash()`. Plain-text is never stored. |
| `firstname` | `VARCHAR(50)` | Yes | None | NULL | First name of the account holder. |
| `lastname` | `VARCHAR(50)` | Yes | None | NULL | Last name of the account holder. |
| `usertype` | `VARCHAR(50)` | Yes | None | `'user'` | Role name (`'admin'`, `'seller'`, `'user'`) for backward-compatible session checking. |
| `two_factor_enabled`| `TINYINT(1)` | No | None | `0` | Flag (`1` = enabled, `0` = disabled) requiring TOTP authentication. |
| `two_factor_secret` | `VARCHAR(64)` | Yes | None | NULL | Encrypted Base32 secret key shared with Google Authenticator. |
| `failed_attempts` | `INT(11)` | No | None | `0` | Counter for consecutive failed login attempts; resets to 0 upon success. |
| `lockout_until` | `DATETIME` | Yes | None | NULL | Timestamp until which login attempts are rejected (locks for 15 mins after 3 fails). |
| `login_count` | `INT(11)` | No | None | `0` | Running count of successful authentications for anomaly detection. |
| `created_at` | `TIMESTAMP` | No | None | `CURRENT_TIMESTAMP` | Audit timestamp of account creation. |
| `updated_at` | `TIMESTAMP` | No | None | `CURRENT_TIMESTAMP` | Automatically updated timestamp of last profile alteration. |

---

#### Table: `roles`
*Enforces Role-Based Access Control (RBAC) permissions throughout the system.*

| Column Name | Data Type | Nullable | Key | Default | Description |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `role_id` | `INT(11)` | No | PK, AI | Auto | Unique role identifier. |
| `role_name` | `VARCHAR(50)` | No | UK | None | Role designation (`'admin'`, `'seller'`, `'user'`, `'guest'`). |
| `description` | `VARCHAR(255)`| Yes | None | NULL | Human-readable role responsibilities. |
| `permissions` | `TEXT` | Yes | None | NULL | JSON-encoded permission capabilities. |

---

#### Table: `audit_logs`
*Provides an immutable forensic log of all critical security actions.*

| Column Name | Data Type | Nullable | Key | Default | Description |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `log_id` | `INT(11)` | No | PK, AI | Auto | Primary key for forensic audit entry. |
| `user_id` | `INT(11)` | Yes | FK | NULL | ID of actor (NULL if unauthenticated visitor/bot). |
| `action` | `VARCHAR(100)`| No | None | None | Event name (`'LOGIN_SUCCESS'`, `'FAILED_LOGIN'`, `'LOCKOUT'`, `'2FA_ENABLED'`). |
| `ip_address` | `VARCHAR(45)` | Yes | None | NULL | IPv4 or IPv6 of the requesting client. |
| `user_agent` | `VARCHAR(255)`| Yes | None | NULL | Browser/OS user agent string for fingerprinting. |
| `details` | `TEXT` | Yes | None | NULL | Additional diagnostic payload (e.g. failure reason). |
| `created_at` | `TIMESTAMP` | No | None | `CURRENT_TIMESTAMP` | Immutable timestamp. |

---

#### Table: `book_returns`
*Manages physical book return lifecycles, condition inspection results, and fee settlements.*

| Column Name | Data Type | Nullable | Key | Default | Description |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `return_id` | `INT(11)` | No | PK, AI | Auto | Primary key of return request. |
| `rental_id` | `INT(11)` | No | FK | None | References `book_rentals(rental_id)`. |
| `user_id` | `INT(11)` | No | FK | None | References `users(id)` (Renter). |
| `seller_id` | `INT(11)` | No | FK | None | References `sellers(id)` (Lender / Store Owner). |
| `book_id` | `INT(11)` | No | FK | None | References `books(book_id)`. |
| `return_method`| `VARCHAR(20)` | No | None | `'dropoff'` | `'dropoff'` (Campus Meet-up) or `'pickup'` (Courier). |
| `return_details`| `TEXT` | Yes | None | NULL | JSON storing address, scheduled date/time, and meet-up instructions. |
| `status` | `ENUM(...)` | No | None | `'pending'` | `'pending'`, `'received'`, `'completed'`, `'cancelled'`. |
| `request_date`| `DATETIME` | No | None | `CURRENT_TIMESTAMP`| Submission date. |
| `received_date`| `DATETIME` | Yes | None | NULL | Timestamp of physical book handover receipt. |
| `completed_date`| `DATETIME` | Yes | None | NULL | Timestamp of final condition inspection & restock. |
| `book_condition`| `ENUM(...)` | Yes | None | NULL | Assessed condition: `'excellent'`, `'good'`, `'fair'`, `'damaged'`. |
| `late_fee` | `DECIMAL(10,2)`| No | None | `0.00` | Automated late return fee. |
| `damage_fee` | `DECIMAL(10,2)`| No | None | `0.00` | Fee assessed for physical damage. |
| `additional_fee`| `DECIMAL(10,2)`| No | None | `0.00` | Miscellaneous / courier adjustment fee. |

---

## D. System Flowchart

### Complete Authentication, Verification & Access Control Flowchart

This flowchart outlines the end-to-end security pipeline from the moment a user initiates a login request through credential validation, brute-force defense, multi-factor verification, session establishment, role authorization, and audit logging.

```mermaid
flowchart TD
    Start([Start: User Enters Login Credentials & reCAPTCHA]) --> CheckCaptcha{Is Google reCAPTCHA Valid?}

    %% Bot Check
    CheckCaptcha -- No (Bot Detected) --> LogBot[Log 'BOT_ATTEMPT_BLOCKED' in audit_logs]
    LogBot --> ShowCaptchaError[Display Error: 'reCAPTCHA verification failed. Please try again.']
    ShowCaptchaError --> EndDenied([Access Denied])

    %% reCAPTCHA Passed
    CheckCaptcha -- Yes (Human Confirmed) --> SanitizeInput[Sanitize Email / Username & Password Input]
    SanitizeInput --> QueryUser[Execute Prepared Statement:<br/>SELECT * FROM users WHERE email = ? OR username = ?]
    QueryUser --> UserExists{User Record Found?}

    %% User Not Found
    UserExists -- No --> LogBadUser[Log 'UNKNOWN_USER_LOGIN_FAILED' in audit_logs]
    LogBadUser --> GenericError[Display Generic Message: 'Invalid email or password.']
    GenericError --> EndDenied

    %% User Found
    UserExists -- Yes --> CheckLockout{Is failed_attempts >= 3<br/>AND lockout_until > NOW()?}
    
    %% Account Locked
    CheckLockout -- Yes (Account Locked) --> CalcTime[Calculate Remaining Lockout Minutes]
    CalcTime --> LogLocked[Log 'LOCKED_ACCOUNT_ATTEMPT' in audit_logs]
    LogLocked --> ShowLockMsg[Display Error: 'Account temporarily locked. Try again in X minutes.']
    ShowLockMsg --> EndDenied

    %% Account Not Locked
    CheckLockout -- No (Eligible to Authenticate) --> VerifyPassword{Does password_verify<br/>input_password, stored_hash<br/>Return TRUE?}

    %% Password Failed
    VerifyPassword -- No (Wrong Password) --> IncFail[failed_attempts = failed_attempts + 1]
    IncFail --> CheckNewLock{Did failed_attempts Reach 3?}
    CheckNewLock -- Yes --> SetLock[lockout_until = NOW + 15 Minutes]
    SetLock --> LogLockEvent[Log 'ACCOUNT_LOCKED_BRUTE_FORCE' in audit_logs]
    LogLockEvent --> GenericError
    CheckNewLock -- No --> LogFail[Log 'LOGIN_FAILED_BAD_PASSWORD' in audit_logs]
    LogFail --> GenericError

    %% Password Succeeded
    VerifyPassword -- Yes (Password Match) --> ResetLock[Reset failed_attempts = 0<br/>lockout_until = NULL]
    ResetLock --> Check2FA{Is two_factor_enabled == 1?}

    %% 2FA Verification Flow
    Check2FA -- Yes --> Prompt2FA[Prompt for 6-Digit TOTP Authenticator Code]
    Prompt2FA --> VerifyTOTP{Is TOTP Code Valid<br/>within 30s Window?}
    VerifyTOTP -- No --> Log2FAFail[Log '2FA_VERIFICATION_FAILED' in audit_logs]
    Log2FAFail --> Show2FAError[Display Error: 'Invalid or expired 2FA code.']
    Show2FAError --> EndDenied
    VerifyTOTP -- Yes --> EstablishSession[Proceed to Session Establishment]

    %% No 2FA required
    Check2FA -- No --> EstablishSession

    %% Session Security & Role Routing
    EstablishSession --> GenSession[Regenerate Session ID:<br/>session_regenerate_id true]
    GenSession --> SetSessionVars[Set Session Data:<br/>$_SESSION'id' = user.id<br/>$_SESSION'usertype' = user.usertype<br/>$_SESSION'loggedin' = true]
    SetSessionVars --> LogSuccess[Log 'LOGIN_SUCCESS' in audit_logs with IP & User-Agent]
    LogSuccess --> CheckRole{Inspect User Role: user.usertype}

    %% Role-Based Access Control Routing
    CheckRole -- 'admin' --> RouteAdmin[Redirect to admin_dashboard.php<br/>Enforce Admin Access Control]
    CheckRole -- 'seller' --> RouteSeller[Redirect to seller_dashboard.php<br/>Enforce Seller Access Control]
    CheckRole -- 'user' --> RouteUser[Redirect to dashboard.php / account.php<br/>Enforce Customer Access Control]

    RouteAdmin --> EndGranted([Access Granted: Authorized Environment])
    RouteSeller --> EndGranted
    RouteUser --> EndGranted
```

### Step-by-Step Flowchart Narrative

1. **Step 1: Initiation & Bot Check:**
   The user submits their email/username, password, and Google reCAPTCHA response token. If the reCAPTCHA token fails or is missing, a `BOT_ATTEMPT_BLOCKED` audit log entry is written and execution halts immediately.

2. **Step 2: Credential Retrieval via Prepared Statements:**
   If human identity is verified, the input is sanitized. PHP executes a parameterized query (`SELECT * FROM users WHERE email = ?`) to safely fetch the user record without any vulnerability to SQL Injection.

3. **Step 3: Brute-Force Lockout Gate:**
   The system inspects `failed_attempts` and `lockout_until`. If 3 consecutive failures have occurred within the last 15 minutes, access is rejected immediately with a friendly remaining-time message, mitigating automated dictionary and credential-stuffing attacks.

4. **Step 4: Cryptographic Password Verification:**
   The submitted plaintext password is evaluated against the stored hash using `password_verify($password, $user['password'])`.
   * **If Failure:** `failed_attempts` is incremented. If it hits 3, `lockout_until` is set to 15 minutes in the future, an `ACCOUNT_LOCKED_BRUTE_FORCE` entry is logged, and a generic error is shown. (Generic error messages prevent account enumeration).
   * **If Success:** `failed_attempts` is reset to 0, and `lockout_until` is cleared.

5. **Step 5: Two-Factor Authentication (2FA) Challenge:**
   The system checks whether `two_factor_enabled == 1`. If enabled, the user is redirected to `verify_2fa.php`. The user must supply a 6-digit TOTP code generated by Google Authenticator. The server verifies this against the user's `two_factor_secret` using a ±1 time window (allowing 30 seconds clock drift).

6. **Step 6: Session Hardening:**
   Upon complete verification, `session_regenerate_id(true)` is invoked to eliminate session fixation risks. Key session variables are set (`$_SESSION['loggedin'] = true`, `$_SESSION['id']`, `$_SESSION['usertype']`).

7. **Step 7: Role Authorization & Route Dispatch:**
   The system queries the verified `usertype`:
   * `admin` accounts are routed to the Administrative Dashboard and Audit Log Monitor.
   * `seller` accounts are routed to the Seller Workspace and Inventory Management.
   * `user` accounts are routed to the Customer Dashboard and Book Catalog.
   A `LOGIN_SUCCESS` record containing the user's ID, IPv4/IPv6 address, and browser signature is permanently recorded in `audit_logs`.

---

### Verification and Compliance Summary

| Requirement Item from Exam Outline | How It Is Addressed in Part III |
| :--- | :--- |
| **A. System Architecture** | Detailed 4-tier structural diagram showing User ➔ Presentation ➔ Security Layer ➔ Database with full component descriptions. |
| **B. Use Case Diagram** | Diagram and specification matrix mapping Administrator, Registered User, Verified Seller, and Guest against core functions and security requirements. |
| **C. Database Design (ERD)** | Complete Mermaid ERD with cardinalities and comprehensive Data Dictionaries featuring security tables (`users`, `roles`, `audit_logs`) and domain tables (`books`, `cart`, `orders`, `book_returns`, etc.). |
| **D. System Flowchart** | Flowchart illustrating the path: `Login ➔ Validate Credentials ➔ Check Password ➔ Check Role ➔ Grant/Deny Access`, including 2FA and account lockout. |
