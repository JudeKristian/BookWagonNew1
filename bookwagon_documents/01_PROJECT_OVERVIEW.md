# BookWagon: Comprehensive Project Overview
### A Secure Web-Based Platform for Sustainable Book Rental, Resale, and Barter
**Course:** Information Assurance and Security 2 (IAS 2)  
**Academic Project Document:** Module 1 — Introduction, Problem Statement, Proposed Solution, and Objectives

---

## 1. Project Introduction & Background

Books, textbooks, academic references, and literature remain indispensable pillars of higher education and personal enrichment. However, students and avid readers face mounting financial barriers due to the escalating costs of brand-new physical editions. Most university textbooks are utilized for a single semester (approximately 3 to 4 months) before being relegated to bookshelves or discarded, leading to substantial financial loss for students and contributing to avoidable paper waste.

Traditional e-commerce platforms primarily cater to one-way retail sales with high intermediary commissions, rigid logistics, and delayed delivery times that are ill-suited for students requiring immediate course references. Furthermore, informal peer-to-peer exchanges (such as social media groups or campus message boards) lack accountability: transactions suffer from counterfeit claims, lost books, unreturned rentals, damaged physical conditions, and predatory interactions without safety nets.

**BookWagon** was conceived to solve these challenges by creating a secure, trusted, circular economy web application specifically tailored for academic communities and book lovers. The platform empowers users to rent, purchase second-hand, and barter physical books locally with verified peers, safeguarded by modern **Information Assurance and Security (IAS)** controls, escrow financial protection, and physical meetup handshakes.

---

## 2. Problem Statement

Through campus surveys and market analysis, several fundamental pain points were identified:

### 2.1 Prohibitive Academic Book Costs
Students frequently spend thousands of pesos every academic year purchasing textbooks and review references that they only read for a few months. When the semester concludes, there are few viable avenues to recoup these expenses.

### 2.2 Lack of Trust & Accountability in Peer-to-Peer Rentals
When students rent books to acquaintances or strangers via informal channels:
- Borrowers often fail to return books on time, or return them heavily damaged (torn pages, water damage, excessive highlighting).
- Owners have no financial recourse to recover the loss of their property because informal exchanges lack security deposits or binding agreements.

### 2.3 Absence of Condition Verification
Disputes regularly erupt regarding the pre-existing condition of rented books. A borrower may claim that a book was already damaged when received, while the owner insists it was in pristine condition. Without a verifiable baseline, determining accountability is subjective.

### 2.4 Cyber Threats and Insecure Web Architecture
Existing student-built marketplace solutions frequently fall victim to common web application vulnerabilities:
- **SQL Injection (SQLi):** Malicious actors inject SQL queries to exfiltrate user data, bypass authentication, or wipe database tables.
- **Account Takeover & Brute-Force Attacks:** Weak password storage and absence of multi-factor authentication allow attackers to compromise accounts through dictionary attacks or automated credential stuffing.
- **Unauthorized Database Tampering:** Administrators or rogue actors with direct database access (e.g., via phpMyAdmin or backend scripts) can alter product prices, student wallet balances, or erase transaction histories without leaving an audit trail.
- **Malicious File Uploads:** Attackers upload disguised PHP web shells via profile picture or book cover upload fields to execute arbitrary code on the server.

---

## 3. Proposed Solution: The BookWagon Ecosystem

BookWagon bridges the gap between sustainability, affordability, and digital security through a unified web platform:

### 3.1 Circular Economy for Books
BookWagon enables three distinct transaction channels:
1. **Rental with Flexible Durations:** Owners list books with affordable weekly rental fees while retaining long-term ownership.
2. **Outright Resale:** Users sell previously owned books to peers at accessible second-hand prices.
3. **Peer-to-Peer Book Swapping (Barter):** Users exchange books directly without exchanging money, proposing direct trades based on literary interest.

### 3.2 Platform Escrow & Financial Protection
To eliminate financial fraud, BookWagon implements an **Escrow Settlement Architecture**:
- When a renter books an item, the rental fee plus a **refundable security deposit** are collected and securely held in platform escrow.
- The owner is assured that if the book is destroyed or not returned, the deposit can be forfeited to cover the replacement cost.
- The renter is assured that once the book is returned in good condition, their security deposit is promptly refunded to their wallet.

### 3.3 Physical Meetup "Double-Handshake" Verification
To prevent condition disputes during campus meetups:
1. **Baseline Condition Photo:** When preparing to hand over the book, the seller uploads a real-time condition photo capturing cover, spine, and page condition (`initial_condition_photo`).
2. **Cryptographic QR Code Validation:** The borrower inspects the physical book against the photo, and if satisfied, scans the seller's secure QR code.
3. **Transaction Lock:** The scan validates both parties' presence and locks the mutual handover timestamp in the database.

### 3.4 Middleman Dispute Arbitration Portal
If a book is returned damaged, the seller submits a return inspection report. If the renter disputes the assessment, the transaction escalates to the **Administrator Dispute Portal** (`Admin/admin_disputes.php`), where administrators examine pre-handover photos versus post-return damage evidence to fairly distribute escrow funds.

### 3.5 Enterprise-Grade Information Assurance & Security
BookWagon embeds **17 dedicated security mechanisms** across the presentation, business logic, session, database, and forensic layers. This includes 100% prepared statements, Bcrypt password hashing, Google Authenticator TOTP 2FA, 3-attempt account lockout, defense-in-depth file inspection, and autonomous MariaDB database intrusion detection triggers that catch unauthorized database manipulation.

---

## 4. Project Objectives

### 4.1 General Objective
To design, develop, test, and deploy a secure, responsive, and trustworthy web-based book rental, resale, and swapping platform that lowers educational costs, encourages sustainable book reuse, and provides end-to-end security compliance according to IAS 2 standards.

### 4.2 Specific Technical Objectives
1. **Implement Robust Authentication & Identity Management:**
   - Integrate multi-tier authentication combining Google reCAPTCHA v2 bot mitigation, salted Bcrypt hashing, 15-minute brute-force account lockouts, and RFC 6238 TOTP Two-Factor Authentication.
2. **Guarantee Data Integrity & Injection Defense:**
   - Eliminate 100% of SQL Injection vulnerabilities across all transactional queries through strict MySQLi prepared statements and parameterized inputs.
3. **Deploy Autonomous Database Intrusion Detection:**
   - Engineer MariaDB triggers (`trg_audit_book_update`, `trg_audit_book_delete`, `trg_audit_user_update`) to detect and flag unauthorized out-of-band SQL modifications (such as manual price inflation or wallet manipulation in phpMyAdmin) into an immutable forensic audit log with administrative remediation workflows.
4. **Secure File Upload Pipeline:**
   - Enforce rigorous server-side MIME type verification (`FILEINFO_MIME_TYPE`), extension whitelisting, size restrictions (max 5MB), and unguessable cryptographic file renaming for book covers and KYC identification documents.
5. **Protect Financial Transactions via Escrow:**
   - Build an automated escrow ledger managing rental charges and security deposits with automated release upon verified return and manual dispute arbitration for damaged or lost books.
6. **Provide Transparent Auditing & Forensics:**
   - Maintain comprehensive audit trails capturing client IP addresses (`REMOTE_ADDR`), timestamps, browser User-Agent footprints, and action metadata.

---

## 5. Target Stakeholders & Scope

| Stakeholder Role | Functional Responsibilities & Capabilities |
| :--- | :--- |
| **Guest / Visitor** | Explores the public catalog, searches books by title/genre/author, reviews rental terms, and registers an account under Google reCAPTCHA protection. |
| **Registered Buyer / Renter** | Searches catalog, rents books with escrow deposits, purchases books, proposes barter swaps, manages personal wallet balance, scans meetup QR codes, and submits return handovers. |
| **Verified Seller / Bookowner** | Submits KYC identity documents, lists books with pricing/rental rates, uploads meetup condition photos, inspects returned books, accepts/rejects barter swaps, and requests payout withdrawals. |
| **System Administrator** | Oversees platform health, monitors autonomous database intrusion alerts, arbitrates return damage disputes, audits financial escrow transactions, verifies seller KYC applications, and reviews system logs. |

---

## 6. Technical Stack & Environment

- **Server Environment:** Apache HTTP Server 2.4 (XAMPP 8.2)
- **Programming Language:** PHP 8.2 (Backend Controller & Business Logic)
- **Database Engine:** MariaDB 10.4+ / MySQL (Relational Schema, Foreign Keys, Engine Triggers)
- **Frontend Technologies:** HTML5, Modern CSS3, JavaScript (ES6+), Bootstrap 5.3
- **Security Libraries:** 
  - `password_hash()` (Bcrypt implementation)
  - Google reCAPTCHA v2 REST API (Bot mitigation)
  - PHPGangsta / RFC 6238 TOTP (Google Authenticator 2FA)
  - PHP Fileinfo (`finfo_open` for true MIME verification)
