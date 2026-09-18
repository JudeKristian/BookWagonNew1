# BookWagon: System Architecture, Workflows & Process Flows
### A Secure Web-Based Platform for Sustainable Book Rental, Resale, and Barter
**Course:** Information Assurance and Security 2 (IAS 2)  
**Academic Project Document:** Module 2 — Multi-Tier System Architecture and Comprehensive Process Flows

---

## 1. Multi-Tier Defense-in-Depth Architecture

BookWagon implements a **Defense-in-Depth Multi-Tier Web Architecture**. Every user action passes through strict validation, cryptographic verification, session hardening, and database integrity checks before records are committed to persistent storage.

```mermaid
graph TD
    subgraph Client_Tier["1. CLIENT / PRESENTATION TIER"]
        UserBrowser["User Web Browser (Desktop / Mobile)"]
        UIElements["Responsive Web Interface (Bootstrap 5, CSS3, JS)"]
        ClientValidation["Client-Side Form Validation & Google reCAPTCHA v2 Widget"]
    end

    subgraph Web_App_Tier["2. WEB APPLICATION TIER (XAMPP / Apache / PHP 8.2)"]
        WebServer["Apache HTTP Server"]
        RoutingEngine["Controller / Page Handlers (PHP Scripts)"]
        SessionStore["PHP Session Management (guest_session.php / session.php)"]
    end

    subgraph Security_Layer["3. AUTHENTICATION & SECURITY ENFORCEMENT LAYER"]
        CaptchaVerifier["Google reCAPTCHA Verification API"]
        BruteForceLockout["3-Attempt Account Lockout Rate Limiter"]
        PasswordVerifier["Bcrypt Hash Verification (password_verify)"]
        TwoFactorEngine["2FA Engine (Google Authenticator TOTP / Email OTP)"]
        RBACGuard["Role-Based Access Control (RBAC Guard)"]
        UploadSecurity["Secure File Upload Pipeline (MIME, Extension, Size, Hash)"]
        SQLDefenses["SQL Injection Prevention (100% Prepared Statements)"]
        AuditLogger["Audit Trail Logger (audit_logs & login_history)"]
    end

    subgraph Data_Tier["4. SECURE DATABASE TIER (MariaDB / MySQL)"]
        DBInstance[("bookwagon_db")]
        SecurityTables["Security Tables (users, admin, audit_logs, login_history)"]
        CommerceTables["Commerce Tables (books, cart, orders, book_rentals, book_returns)"]
        DBTriggers["Autonomous MariaDB Triggers (trg_audit_book_update, trg_audit_book_delete, trg_audit_user_update)"]
    end

    UserBrowser -->|HTTP/HTTPS Request| WebServer
    WebServer --> RoutingEngine
    RoutingEngine --> CaptchaVerifier
    CaptchaVerifier -->|Verified Human| BruteForceLockout
    BruteForceLockout -->|Not Locked Out| PasswordVerifier
    PasswordVerifier -->|Valid Hash| TwoFactorEngine
    TwoFactorEngine -->|Verified TOTP / Bypass| SessionStore
    SessionStore --> RBACGuard
    RBACGuard --> UploadSecurity
    UploadSecurity --> SQLDefenses
    SQLDefenses -->|Parameterized Query| DBInstance
    DBInstance --> SecurityTables
    DBInstance --> CommerceTables
    CommerceTables -.->|Direct DB Modification Attempt| DBTriggers
    DBTriggers -->|Autonomously Flag 'RISK'| SecurityTables
    AuditLogger --> SecurityTables
```

---

## 2. Core System Process Flows

### Flow 1: User Registration & Authentication Flow

This workflow illustrates how users safely register and authenticate, featuring Google reCAPTCHA v2 bot protection, 3-attempt account lockout, Bcrypt password verification, two-factor authentication (TOTP/OTP), session regeneration, and role dispatch.

```mermaid
flowchart TD
    Start([User Initiates Login]) --> InputForm[Enter Email & Password + Solve reCAPTCHA]
    InputForm --> PostLogin[Submit POST to login.php]
    
    %% Bot Protection
    PostLogin --> VerifyCaptcha{Is reCAPTCHA<br/>Verified with Google?}
    VerifyCaptcha -- No --> BotBlocked[Block Submission<br/>Display 'reCAPTCHA failed']
    BotBlocked --> EndDenied([Access Denied])
    
    %% Rate Limiting / Brute Force Check
    VerifyCaptcha -- Yes --> CheckLockout{Is failed_attempts >= 3<br/>within last 15 mins?}
    CheckLockout -- Yes --> LockoutActive[Display 'Account locked.<br/>Try again in X minutes.']
    LockoutActive --> EndDenied
    
    %% SQL Fetch via Prepared Statements
    CheckLockout -- No --> QueryUser[Execute Prepared Query:<br/>SELECT id, password, is_2fa_enabled, is_verified<br/>FROM users WHERE email = ?]
    QueryUser --> UserExists{User Exists in Database?}
    UserExists -- No --> IncFail[Increment failed_attempts<br/>Log Failed Attempt]
    IncFail --> BadCredsMsg[Display 'Invalid email or password']
    BadCredsMsg --> EndDenied
    
    %% Password Verification
    UserExists -- Yes --> CheckEmailVerified{Is is_verified == 1?}
    CheckEmailVerified -- No --> EmailUnverifiedMsg[Display 'Please verify your email address']
    EmailUnverifiedMsg --> EndDenied
    
    CheckEmailVerified -- Yes --> VerifyBcrypt{Does password_verify<br/>input_password, stored_hash<br/>return TRUE?}
    VerifyBcrypt -- No --> IncFail
    
    %% Successful Password Match
    VerifyBcrypt -- Yes --> ResetAttempts[Reset failed_attempts = 0]
    ResetAttempts --> Check2FA{Is is_2fa_enabled == 1?}
    
    %% 2FA Challenge Branch
    Check2FA -- Yes --> Redirect2FA[Redirect to verify_2fa.php]
    Redirect2FA --> Submit2FACode[User Enters 6-Digit TOTP / Backup Code]
    Submit2FACode --> ValidateTOTP{Is Code Valid?}
    ValidateTOTP -- No --> 2FAFailed[Display 'Invalid Code']
    2FAFailed --> EndDenied
    ValidateTOTP -- Yes --> SessionInit
    
    %% Direct Login Branch (2FA disabled for demo/opt-out)
    Check2FA -- No --> SessionInit[Regenerate Session ID:<br/>session_regenerate_id true]
    
    %% Session Establishment & Audit Logging
    SessionInit --> SetSession[Set Session Variables:<br/>loggedin = true, user_id, usertype, email]
    SetSession --> LogSuccess[Insert record in audit_logs & login_history]
    SetSession --> CheckRole{Inspect User Role}
    
    %% RBAC Routing
    CheckRole -- 'seller' --> GoSeller[Redirect to seller_dashboard.php]
    CheckRole -- 'user' --> GoHome[Redirect to home.php]
    
    GoSeller --> EndGranted([Authorized Dashboard Session])
    GoHome --> EndGranted
```

---

### Flow 2: Book Rental & Platform Escrow Protection Flow

This workflow guarantees financial safety between renters and owners. Renter payments and refundable security deposits are held in platform escrow until books are returned safely.

```mermaid
flowchart TD
    Browse[Renter Browses Book Catalog in home.php / book_details.php] --> SelectBook[Select Book & Click 'Rent Book']
    SelectBook --> ChooseDuration[Choose Rental Duration:<br/>1 Week, 2 Weeks, 1 Month]
    ChooseDuration --> ViewBreakdown[System Calculates Breakdown:<br/>Rental Fee + Security Deposit]
    ViewBreakdown --> AddToCart[Add to Cart & Proceed to checkout.php]
    
    %% Checkout
    AddToCart --> SelectPayment[Select Payment Method: Wallet / GCash / Maya]
    SelectPayment --> SubmitCheckout[Confirm Checkout & Lock Escrow]
    SubmitCheckout --> EscrowHold[Deduct Total from Renter Wallet<br/>Hold Total in Platform Escrow<br/>Create book_rentals Record with status = 'pending_meetup']
    
    %% Meetup Handover
    EscrowHold --> NotifySeller[Notify Seller of New Rental Order]
    NotifySeller --> SellerPrepare[Seller Prepares Book & Meets Renter]
    SellerPrepare --> DoubleHandshake[Perform Physical Meetup Double-Handshake]
    
    %% Active Rental State
    DoubleHandshake --> ActiveRental[Status Updated to 'active'<br/>Rental Countdown Timer Starts]
    ActiveRental --> ReminderNotice[System Sends Return Due Date Notifications]
    
    %% Return Flow
    ReminderNotice --> ReturnMeetup[Parties Meet for Physical Return]
    ReturnMeetup --> SellerInspect{Seller Inspects Returned Book}
    
    %% Return Pathways
    SellerInspect -- In Good Condition --> ReturnApproved[Seller Approves Return in process_return_seller.php]
    ReturnApproved --> ReleaseEscrow[Release Security Deposit back to Renter Wallet<br/>Transfer Rental Fee to Seller Earnings<br/>Status = 'completed']
    
    SellerInspect -- Damaged or Lost --> FileDispute[Seller Submits Damage Claim with Photos]
    FileDispute --> DisputeReview[Transaction Escalated to Admin Dispute Portal]
    
    ReleaseEscrow --> RentalEnd([Rental Cycle Finalized])
    DisputeReview --> RentalEnd
```

---

### Flow 3: Physical Meetup "Double-Handshake" Verification Flow

To eliminate disputes over pre-existing scratches, tears, or stains, BookWagon requires a double-handshake physical meetup verification process before an item is marked handed over.

```mermaid
sequenceDiagram
    autonumber
    actor Renter as Renter (Student)
    actor Seller as Book Owner (Seller)
    participant Platform as BookWagon Core Server
    participant DB as MariaDB (bookwagon_db)

    Note over Renter, Seller: Parties Agree on Meetup Campus Location
    Seller->>Platform: Open seller_confirm_handover.php
    Seller->>Platform: Upload Live Baseline Condition Photo (initial_condition_photo)
    Platform->>DB: Store Photo Path & Set condition_submitted = 1
    Platform-->>Seller: Generate Dynamic Encrypted QR Code & Verification Token
    
    Note over Renter, Seller: Physical Face-to-Face Meeting
    Seller->>Renter: Present Physical Book for Visual Inspection
    Renter->>Platform: Open renter_handover.php
    Platform-->>Renter: Display Seller's Baseline Condition Photo for Comparison
    Renter->>Renter: Inspect Physical Book vs. Baseline Photo
    
    alt Book Condition Verified & Matches
        Renter->>Platform: Scan Seller's QR Code via Camera / Enter Handshake PIN
        Platform->>Platform: Validate QR Hash against Session Token
        Platform->>DB: Update book_rentals SET status = 'active', handover_at = NOW()
        Platform->>DB: Record 'HANDOVER_VERIFIED' in audit_logs
        Platform-->>Renter: Handover Confirmed! Rental Timer Starts.
        Platform-->>Seller: Handover Confirmed! Book Transferred.
    else Book Condition Does Not Match / Severe Damage Found
        Renter->>Platform: Reject Handshake & Cancel Meetup
        Platform->>DB: Cancel Rental, Return Full Escrow Deposit & Fee to Renter Wallet
        Platform-->>Renter: Full Refund Issued to Wallet
        Platform-->>Seller: Order Cancelled Due to Unmet Condition Standards
    end
```

---

### Flow 4: Book Return Inspection, Damage Claims & Admin Arbitration Flow

When a rental period ends, the book must be returned. If the owner claims damage or the renter disputes the claim, BookWagon's administrative arbitration workflow acts as an impartial middleman.

```mermaid
flowchart TD
    DueReached[Rental Period Concludes] --> ScheduleReturn[Parties Meet to Hand Back Book]
    ScheduleReturn --> SellerExamines[Seller Receives Book & Inspects Condition]
    
    SellerExamines --> ConditionCheck{Is Book in Same Condition<br/>as Initial Baseline Photo?}
    
    %% Undamaged Pathway
    ConditionCheck -- Yes (No Damage) --> SellerConfirm[Seller Clicks 'Approve Return' in seller_dashboard.php]
    SellerConfirm --> AutoSettle[Platform Automated Settlement:<br/>1. Refund 100% Security Deposit to Renter Wallet<br/>2. Disburse Rental Earnings to Seller Wallet<br/>3. Increment Book Inventory Stock by 1]
    AutoSettle --> SettleComplete([Transaction Successfully Closed])
    
    %% Damaged Pathway
    ConditionCheck -- No (Damaged / Missing Pages / Lost) --> SellerClaim[Seller Flags 'Report Damage' in process_return_seller.php]
    SellerClaim --> UploadEvidence[Seller Uploads Return Condition Photos & Specified Deductions]
    UploadEvidence --> NotifyRenter[System Notifies Renter of Damage Claim]
    
    NotifyRenter --> RenterResponse{Does Renter Agree<br/>with Damage Assessment?}
    
    %% Mutual Agreement
    RenterResponse -- Yes (Accepts Deduction) --> DeductDeposit[Deduct Agreed Repair/Replacement Fee from Escrow Deposit<br/>Credit Remainder to Renter<br/>Credit Damage Compensation to Seller]
    DeductDeposit --> SettleComplete
    
    %% Disputed Claim
    RenterResponse -- No (Contests Claim) --> EscalateAdmin[Transaction Escalates to Admin/admin_disputes.php]
    EscalateAdmin --> AdminInvestigates[Administrator Reviews Case:<br/>1. Compares initial_condition_photo vs. return_photo<br/>2. Reviews Chat Transcripts and Meetup Logs]
    
    AdminInvestigates --> AdminRuling{Admin Arbitrator Ruling}
    AdminRuling -- In Favor of Seller --> AdminSellerWin[Deduct Assessed Penalty from Deposit to Seller<br/>Refund Balance to Renter<br/>Record 'DISPUTE_SETTLED_SELLER' in audit_logs]
    AdminRuling -- In Favor of Renter --> AdminRenterWin[Overrule Claim: Full Escrow Deposit Refunded to Renter<br/>Record 'DISPUTE_SETTLED_RENTER' in audit_logs]
    
    AdminSellerWin --> SettleComplete
    AdminRenterWin --> SettleComplete
```

---

### Flow 5: Peer-to-Peer Book Swap / Barter Proposal Flow

The BookWagon swap system allows readers to trade physical books without financial exchange.

```mermaid
flowchart TD
    UserA[Reader A Discovers Book Listed by Reader B in bookswap.php] --> ViewOffer[Click 'Propose Book Swap']
    ViewOffer --> SelectOwnBook[Reader A Selects a Book from Their Own Library to Offer in Exchange]
    SelectOwnBook --> AddProposalNote[Enter Message & Preferred Campus Meetup Location]
    AddProposalNote --> SendProposal[Submit Proposal via ajax_handlers/propose_swap.php]
    
    SendProposal --> CreateProposalRecord[Create Record in book_swaps table with status = 'pending']
    CreateProposalRecord --> AlertReaderB[Send Notification & In-App Alert to Reader B]
    
    AlertReaderB --> ReaderBReviews{Reader B Reviews Proposed Book & Photos}
    
    %% Rejected
    ReaderBReviews -- Reject --> RejectProposal[Reader B Clicks 'Decline']
    RejectProposal --> UpdateDecline[Status = 'declined'<br/>Notify Reader A]
    UpdateDecline --> SwapEnded([Swap Closed])
    
    %% Counter or Discussion
    ReaderBReviews -- Negotiate --> OpenChat[Open Private Messaging in messages.php]
    OpenChat --> ReaderBReviews
    
    %% Accepted
    ReaderBReviews -- Accept --> AcceptProposal[Reader B Clicks 'Accept Swap']
    AcceptProposal --> UpdateAccept[Status = 'accepted'<br/>Both Books Marked as 'in_swap_lock']
    UpdateAccept --> MeetupPlan[Both Readers Coordinate Physical Meetup]
    MeetupPlan --> BothMeet[Readers Meet & Exchange Physical Books]
    BothMeet --> ConfirmExchange[Both Users Click 'Confirm Swap Received']
    ConfirmExchange --> FinalizeSwap[Status = 'completed'<br/>Ownership Transfers in Catalog<br/>Log 'SWAP_COMPLETED' in audit_logs]
    FinalizeSwap --> SwapEnded
```

---

### Flow 6: Autonomous Database Intrusion Detection & Incident Remediation Flow

This security mechanism detects direct out-of-band modifications made directly inside MariaDB (such as a rogue administrator, compromised DB user, or phpMyAdmin hacker altering book prices or wallet balances).

```mermaid
flowchart TD
    Intruder[Malicious Actor or Direct Database User] --> DirectSQL[Executes Direct Raw SQL in phpMyAdmin / CLI:<br/>e.g., UPDATE books SET price = 500 WHERE id = 10;<br/>OR UPDATE users SET wallet_balance = 50000;]
    
    DirectSQL --> MariaDBEngine[MariaDB SQL Engine Processes Command]
    MariaDBEngine --> TriggerFires{Does Table Have an<br/>Autonomous Security Trigger?<br/>trg_audit_book_update<br/>trg_audit_user_update}
    
    TriggerFires -- Yes --> CheckSessionSource{Was Query Generated by<br/>Application Web Session?}
    CheckSessionSource -- No (Out-of-Band Direct Modification) --> EngineAlert[Trigger Intercepts Old & New Values]
    
    EngineAlert --> InsertRiskLog[Trigger Autonomously Inserts Record into audit_logs:<br/>activity = 'UNAUTHORIZED_DATA_MODIFICATION'<br/>status = 'RISK'<br/>details = 'Price altered from 200.00 to 500.00 directly on DB']
    
    InsertRiskLog --> AdminPortal[Admin Logs into admin_dashboard.php]
    AdminPortal --> DetectRiskBanner{Are There Unresolved<br/>RISK Records in audit_logs?}
    
    DetectRiskBanner -- Yes --> ShowRedBanner[Display Urgent Red 'SECURITY WARNING' Banner<br/>Highlighting Detected Anomalies]
    ShowRedBanner --> OpenAuditLogs[Admin Clicks Banner to Open Admin/audit_logs.php]
    
    OpenAuditLogs --> ReviewThreat[Admin Inspects Forensic Details & Compares Diff]
    ReviewThreat --> OpenSolveModal[Admin Clicks 'SOLVE' Button]
    OpenSolveModal --> EnterRemediation[Admin Inputs Mitigation Notes:<br/>e.g. 'Reverted malicious price inflation; inspected credentials']
    
    EnterRemediation --> SubmitSolve[Submit Remediation Form]
    SubmitSolve --> UpdateAuditRecord[Update Record SET status = 'SOLVED', resolution_notes = ?]
    UpdateAuditRecord --> ClearBanner[Security Banner Automatically Disappears]
    ClearBanner --> SystemSecure([System State Restored to Normal])
```
