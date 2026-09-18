# BookWagon: Comprehensive Documentation Suite
### Information Assurance & Security 2 (IAS 2) Academic & Technical Compendium

Welcome to the official documentation suite for **BookWagon: A Secure Web-Based Platform for Sustainable Book Rental, Resale, and Barter**.

---

## 📚 Documentation Modules

This documentation directory is partitioned into four comprehensive modules covering every aspect of the platform from academic problem framing to deep technical security architecture:

1. **[Module 1: Project Overview, Problem & Solution Framework](file:///C:/xampp/htdocs/BookwagonNew1/bookwagon_documents/01_PROJECT_OVERVIEW.md)**
   - Introduction & Background
   - Comprehensive Problem Statement (Educational costs, lack of trust in P2P rentals, condition disputes, cyber risks)
   - Proposed Solution (Circular book economy, platform escrow, double-handshake physical verification, dispute arbitration)
   - General & Specific Objectives
   - Stakeholder Scope & Responsibilities
   - Technical Environment Specifications

2. **[Module 2: System Architecture, Workflows & Process Flows](file:///C:/xampp/htdocs/BookwagonNew1/bookwagon_documents/02_SYSTEM_ARCHITECTURE_AND_FLOWS.md)**
   - Multi-Tier Defense-in-Depth Architecture Diagram
   - **Flow 1:** User Registration, reCAPTCHA, Brute-Force Lockout & 2FA Flowchart
   - **Flow 2:** Book Rental & Platform Escrow Protection Flowchart
   - **Flow 3:** Physical Meetup "Double-Handshake" Verification Sequence Diagram
   - **Flow 4:** Book Return Inspection, Damage Assessment & Admin Arbitration Flowchart
   - **Flow 5:** Peer-to-Peer Book Swap / Barter Proposal Lifecycle
   - **Flow 6:** Autonomous MariaDB Database Intrusion Detection & Remediation ("SOLVE") Flowchart

3. **[Module 3: Information Assurance & Security 2 (IAS 2) Technical Specification](file:///C:/xampp/htdocs/BookwagonNew1/bookwagon_documents/03_SECURITY_IMPLEMENTATION_SPECIFICATION.md)**
   - Deep dive into the **17 distinct security implementations**
   - Detailed breakdown across 5 security domains (Database, Authentication, Session, Application, Auditing)
   - Code-level examples, files involved, and CWE vulnerabilities mitigated for each implementation
   - Defense-in-Depth Security Matrix

4. **[Module 4: Database Design, Schema & Data Dictionary](file:///C:/xampp/htdocs/BookwagonNew1/bookwagon_documents/04_DATABASE_DESIGN_AND_DICTIONARY.md)**
   - Relational Schema & Entity-Relationship Diagram (ERD)
   - SQL definitions for autonomous MariaDB engine triggers (`trg_audit_book_update`, `trg_audit_book_delete`, `trg_audit_user_update`)
   - Complete Data Dictionary detailing the role and schema of all **31 database tables**

---

## 🚀 Quick Links
* Root Application Readme: [`../README.md`](file:///C:/xampp/htdocs/BookwagonNew1/README.md)
* Database Master Export: [`../databases/bookwagon_db.sql`](file:///C:/xampp/htdocs/BookwagonNew1/databases/bookwagon_db.sql)
* Administrator Portal: `http://localhost/BookwagonNew1/Admin/`
* Marketplace / User Portal: `http://localhost/BookwagonNew1/`
