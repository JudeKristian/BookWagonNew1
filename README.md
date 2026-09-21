# BookWagon — Sustainable Book Rental, Resale & Barter Swapping Platform

**Course / Subject:** Information Assurance and Security 2 (IAS 2)  
**Instructor / Evaluator:** Instructor Iluminado Canoy  
**Submitted By (Group 5):**  
* **Abdillah, Alfaidz**  
* **Arig, Reynald**  
* **Larroza, Jude Kristian**  

**Submission Date:** September 2026  
**Environment:** Apache 2.4 / PHP 8.1+ / MySQL / MariaDB (XAMPP)  
**Database Name:** `bookwagon_db`  

---

## 📖 1. Project Overview & Problem Statement

**BookWagon** is a secure, multi-role web marketplace engineered in native PHP and MariaDB/MySQL. It is designed to lower educational textbook costs and promote sustainable reading by supporting:
* **Book Rentals with Escrow Protection:** Automated weekly rental pricing with refundable security deposits held in escrow.
* **Direct Book Resale:** Outright purchase of secondhand textbooks and literature.
* **Peer-to-Peer (P2P) Barter Swapping:** Zero-cash book exchanges with direct reader-to-reader trade proposals.
* **Dual-Party Handover Verification:** Seller baseline condition photos and buyer QR code scanning to guarantee non-repudiation.
* **Administrative Dispute Arbitration:** Photographic evidence comparison (pre-handover vs. post-return) to settle damage disputes.
* **Defense-in-Depth Security:** 17 integrated security controls and autonomous MariaDB database intrusion detection triggers.

BookWagon directly supports **United Nations Sustainable Development Goals (SDG)**:
* **SDG 4 (Quality Education):** Providing affordable access to academic textbooks and learning resources.
* **SDG 12 (Responsible Consumption & Production):** Maximizing the lifecycle and reuse of physical books.

---

## 🚀 2. Quick Setup & Database Import Guide

### Step 1: Place Files in XAMPP
Place the project folder into your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\BookwagonNew1\
```

### Step 2: Choose Your Database File & Import

Open **XAMPP Control Panel**, start **Apache** and **MySQL**, then open **phpMyAdmin**: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)

Click the **"Import"** tab at the top. You have **TWO options** to choose from:

| Option | File Location | Description & Best Use Case |
| :--- | :--- | :--- |
| **Option A (Recommended for Quick Grading)** | `DATABASE/bookwagon_db.sql` | **Full Populated Database:** Contains all 31 tables, triggers, test accounts, and complete catalog listings, seller store profiles, reviews, and demo orders matching the `uploads/` folder. **Best for testing browsing, renting, and admin dispute features immediately without entering new items.** |
| **Option B (Clean Starter Slate)** | `DATABASE/bookwagon_db_clean.sql` | **Clean Starter Database:** Contains the exact same 31 tables, foreign keys, and triggers, but only clean seed accounts and 5 sample books. Zero previous order history or old test chat logs. **Best for testing fresh account registration, new book listing uploads, and clean transactions from scratch.** |

*(Both SQL files automatically execute `CREATE DATABASE IF NOT EXISTS bookwagon_db;` and `USE bookwagon_db;`, so you can import either file directly without manually creating the database first).*

### Step 3: Launch the Application
* **Marketplace / User Portal:** [http://localhost/BookwagonNew1/](http://localhost/BookwagonNew1/)
* **Administrator Portal:** [http://localhost/BookwagonNew1/Admin/](http://localhost/BookwagonNew1/Admin/)

---

## 🔑 3. Evaluation Test Credentials

All evaluation accounts are pre-seeded in both database options:

| Portal | Role | Username / Email | Password | Evaluation Notes |
| :--- | :--- | :--- | :--- | :--- |
| **Admin Portal** | System Administrator | `admin` | `123456789` | Full administrative root access to disputes, KYC, payouts, audit logs, and intrusion triggers |
| **Marketplace** | Official Verified Seller | `seller@bookwagon.com` | `123456789` | **2FA Bypassed** (Instant Login). Store: *"BookWagon Official Store"* |
| **Marketplace** | Student / Renter | `user@bookwagon.com` | `123456789` | **2FA Bypassed** (Instant Login). **Pre-loaded with ₱2,500 wallet balance** for testing rentals |
| **Marketplace** | Alternate Seller | `arielfranco8868@gmail.com` | `123456` | Verified store (available in `bookwagon_db.sql`) |
| **Marketplace** | Alternate Buyer | `sss@gmail.com` | `123456` | Student buyer account (available in `bookwagon_db.sql`) |

> **Note:** The test accounts `seller@bookwagon.com` and `user@bookwagon.com` have Two-Factor Authentication bypassed by default so evaluators can log in instantaneously without needing an external email OTP server.

---

## 🔄 4. Core System Workflows (Evaluator Testing Guide)

```
       ┌────────────────────────────────────────────────────────────────────────┐
       │                       THE BOOKWAGON LIFECYCLE                          │
       │                                                                        │
       │  [1. Discover]  Browse catalog with filters for Sale, Rent, or Barter   │
       │        ↓                                                               │
       │  [2. Escrow]    Renter pays Rental Fee + Security Deposit into Escrow   │
       │        ↓                                                               │
       │  [3. Meetup]    Seller uploads baseline photo; Buyer scans QR Code     │
       │        ↓                                                               │
       │  [4. Active]    Rental timer runs; system tracks active borrow duration │
       │        ↓                                                               │
       │  [5. Return]    Seller inspects book -> 100% Deposit refunded to Renter│
       │        ↓                                                               │
       │  [6. Dispute]   If damaged: Admin arbitrates photos & disburses escrow │
       └────────────────────────────────────────────────────────────────────────┘
```

### Flow 1: Renting a Book with Escrow Protection
1. Log in as Student Buyer (`user@bookwagon.com` / `123456789`).
2. Go to **Explore** or **Rent Books** (`rentbooks.php`).
3. Select a book (e.g., *"Clean Code"* or *"Atomic Habits"*).
4. Click **Rent Now**, choose the rental duration (1 to 4 weeks), and confirm checkout.
5. The system automatically calculates:
   $$\text{Total Paid} = (\text{Weekly Rate} \times \text{Weeks}) + \text{Refundable Security Deposit}$$
   The full security deposit is locked in platform escrow to guarantee safe return.

### Flow 2: Physical Meetup Double-Handshake (Non-Repudiation)
1. Open an Incognito/second browser and log in as Seller (`seller@bookwagon.com` / `123456789`).
2. Navigate to **Seller Dashboard ➔ Rented Books** (`rented_books.php`).
3. Under the active rental, click **Handover**.
4. The seller uploads a real-time condition photo (`initial_condition_photo`) establishing the book's baseline state, and the system presents a dynamic handover QR code.
5. The buyer acknowledges the condition and scans/confirms the QR code. This locks the timestamp and prevents post-transaction disputes about pre-existing damage.

### Flow 3: Return Inspection & Automated Deposit Refund
1. When the rental period ends, the buyer meets the seller to return the book.
2. The seller inspects the physical book.
3. If the book is undamaged, the seller clicks **Approve Return**.
4. **Result:** The system instantly releases 100% of the security deposit back into the buyer's wallet, and credits rental earnings to the seller.

### Flow 4: Damage Dispute & Administrative Photo Arbitration
1. If the returned book has new damage (e.g., torn pages or water damage), the seller submits a **Damage Claim** with a post-return photo.
2. If contested, the transaction moves into **Dispute Status**.
3. Log in as Admin (`admin` / `123456789`) and go to **Admin ➔ Disputes** (`Admin/admin_disputes.php`).
4. The administrator inspects the side-by-side evidence (Pre-Handover Photo vs. Post-Return Photo) and issues an impartial ruling:
   * **Full Refund:** Deposit returned to buyer.
   * **Partial Deduction:** Damage penalty deducted from deposit and transferred to the seller; balance returned to buyer.
   * **Full Forfeit:** 100% deposit transferred to seller for replacement.

### Flow 5: Zero-Cash Peer-to-Peer Barter Swapping
1. Users can list books exclusively for **Swap** (`bookswap.php`).
2. Another student browsing the swap listings clicks **Propose Swap** and selects one of their own books to offer in trade.
3. The owner reviews the trade proposal, communicates via built-in messaging (`messages.php`), and agrees to a campus meetup.

### Flow 6: Autonomous Intrusion Detection & Trigger Alerting
1. Log in to the **Admin Portal** (`http://localhost/BookwagonNew1/Admin/`) and observe the dashboard.
2. Open **phpMyAdmin** and tamper directly with a user's wallet balance or book price in the database:
   ```sql
   UPDATE users SET wallet_balance = 999999.00 WHERE id = 2;
   -- or
   UPDATE books SET price = 9999.00 WHERE book_id = 1;
   ```
3. Refresh the Admin Dashboard: The autonomous MariaDB trigger (`trg_audit_user_update` / `trg_audit_book_update`) immediately fires and displays a prominent **RED THREAT BANNER** (`RISK: DIRECT_WALLET_TAMPERING` or `RISK: DIRECT_PRICE_TAMPERING`).
4. The administrator can click **SOLVE**, record incident notes, and mark the alert as resolved.

---

## 🛡️ 5. The 17 Core IAS 2 Security Implementations

BookWagon was engineered under the **Information Assurance and Security 2 (IAS 2)** curriculum with a multi-layered Defense-in-Depth framework:

| No. | Security Control | Technical Implementation |
| :---: | :--- | :--- |
| **1** | **User Authentication** | Credential-based authentication with session validation and optional Google OAuth 2.0 integration. |
| **2** | **User Registration Guard** | Server-side regex sanitation, duplicate email prevention, and bot protection via Google reCAPTCHA v2. |
| **3** | **Email Verification** | Cryptographic verification tokens sent via PHPMailer to validate genuine student email ownership. |
| **4** | **Bcrypt Password Cryptography** | Salted password hashing via PHP's `password_hash()` with `PASSWORD_DEFAULT` and constant-time `password_verify()`. |
| **5** | **Two-Factor Authentication (2FA)** | Dual-engine 2FA supporting RFC 6238 TOTP (Google Authenticator) and secondary time-limited Email OTPs. |
| **6** | **Brute-Force Rate Limiting** | 3-consecutive-failure threshold triggering an automatic 15-minute account lockout timer (`lockout_until`). |
| **7** | **Google reCAPTCHA v2** | Server-side token verification against Google APIs on public authentication and registration endpoints. |
| **8** | **Google OAuth 2.0 Identity** | OpenID Connect token exchange with Google identity servers for secure third-party authentication. |
| **9** | **Role-Based Access Control (RBAC)** | Strict privilege separation between Administrator, Verified Seller, Registered Student, and Public Guest. |
| **10** | **Server-Side Authorization** | Script-level ownership verification preventing Insecure Direct Object References (IDOR). |
| **11** | **Access Control & Route Guards** | Centralized session gating (`session.php`, `guest_session.php`) redirecting unauthorized visitors. |
| **12** | **Strict Input Validation** | Data sanitization via `htmlspecialchars()`, `filter_var()`, and type casting on all user submissions. |
| **13** | **SQL Injection Prevention** | 100% parameterized prepared statements (`prepare()`, `bind_param()`, `execute()`) across all MySQLi and PDO queries. |
| **14** | **PHP Session Hardening** | `session_regenerate_id(true)` upon privilege changes, strict cookie lifetime, and complete session destruction on logout. |
| **15** | **Audit Trail & Error Shielding** | Immutable logging of security events with IP and device tracking; database technical errors are silenced from user view. |
| **16** | **Non-Repudiation (Dual-Party QR)** | Required seller delivery photos and buyer dynamic QR code signatures locking physical handover state. |
| **17** | **Autonomous Intrusion Triggers** | MariaDB database engine triggers detecting out-of-band tampering with admin risk alerts and resolution tracking. |

---

## 📁 6. Project Directory & Deliverables Structure

```text
BookWagon_Final_Submission/
│
├── GROUP_MEMBERS.txt                     <-- Group members, instructor, course, & test logins
├── QUICK_START_GUIDE.txt                 <-- 2-minute evaluator walkthrough
├── README.md                             <-- Complete documentation & security framework
│
├── DOCUMENTATION/                        <-- Academic deliverables
│   ├── GROUP5_FINAL DOCUMENTATION.pdf    <-- Full 16-page official IAS 2 project document
│   ├── FINAL PRESENTATION.pdf            <-- Presentation slide deck
│   ├── GROUP5_MEMBERS.txt                <-- Detailed group members info
│   └── DIAGRAMS/                         <-- Standalone high-res vector/image diagrams
│       ├── ERD FINAL.pdf & .png          <-- Database Entity-Relationship Diagram (31 tables)
│       ├── FLOWCHART.drawio.pdf & .png   <-- Complete system and handover flowchart
│       ├── SYSTEM ARCHITECTURE DIAGRAM.png <-- Multi-tier defense-in-depth architecture
│       └── USE CASE DIAGRAM.png          <-- System use cases & actor specifications
│
├── DATABASE/                             <-- Database SQL files & instructions
│   ├── bookwagon_db.sql                  <-- Option A: Populated database with complete catalog & orders
│   ├── bookwagon_db_clean.sql            <-- Option B: Clean starter database with seed accounts & triggers
│   └── DATABASE_IMPORT_INSTRUCTIONS.txt  <-- 1-click phpMyAdmin import guide
│
└── SOURCE_CODE/                          <-- Complete application source code
    ├── Admin/                            <-- Administrator portal (moderation, disputes, audit logs)
    ├── ajax_handlers/                    <-- Background AJAX handlers (cart, messaging, forums, buddies)
    ├── api/                              <-- Book swap, listing, and logistics API endpoints
    ├── css/                              <-- Responsive CSS stylesheets
    ├── js/                               <-- Dynamic client-side scripts
    ├── images/                           <-- Static image assets and banners
    ├── include/                          <-- Reusable UI templates (header, sidebar, tab, footer)
    ├── includes/                         <-- Core security engines (audit logger, mailer, 2FA, notifications)
    ├── uploads/                          <-- User-uploaded book covers, condition photos, and receipts
    ├── connect.php                       <-- Centralized database connection
    └── *.php                             <-- Application pages (home, explore, login, rentbooks, etc.)
```

---

## 📚 7. Official Documentation Suite

The complete academic documentation suite is available inside the [`DOCUMENTATION/`](DOCUMENTATION/) directory:
* 📄 **[GROUP5_FINAL DOCUMENTATION.pdf](DOCUMENTATION/GROUP5_FINAL%20DOCUMENTATION.pdf)**: 16-page formal paper covering Problem Statement, 17 Security Controls, Architecture, Use Cases, Database Schema Modules, ERD, and Handover Flowcharts.
* 📊 **[FINAL PRESENTATION.pdf](DOCUMENTATION/FINAL%20PRESENTATION.pdf)**: Project slide deck overview.
* 🖼️ **[DIAGRAMS/](DOCUMENTATION/DIAGRAMS/)**: High-resolution standalone diagram files.
