# ERPGo SaaS — Feature Gap Analysis

> **28 modul terpasang** | Analisis fitur yang **sudah ada** vs **yang harus ditambahkan** untuk ERP produksi-ready.

---

## 1. HRM (Human Resource Management)

###  Sudah Ada
- Struktur organisasi: Branch, Department, Designation
- Employee CRUD + dokumen + emergency contact + bank details
- Set Salary, Allowance, Deduction, Loan, Overtime (per karyawan)
- Payroll: generate, print payslip, mark paid
- Attendance: shift, clock-in/out manual, IP restrict
- Leave: leave types, application, approval, balance tracking
- Discipline: Warning, Complaint, Termination, Resignation, Transfer
- Recognition: Award, Promotion
- Communication: Announcement, Event, Acknowledgment
- HRM Dashboard

###  Belum Ada (Critical)
| Fitur | Prioritas |
|---|---|
| **Employee Self-Service Portal** — login sendiri lihat slip gaji, ajukan cuti, upload dokumen | **CRITICAL** |
| **BPJS + PPh 21 auto-calc** (Indonesia wajib) | **CRITICAL** |
| **THR calculator** | HIGH |
| **Biometric device API** (fingerprint/RFID) | HIGH |
| **GPS attendance** (mobile check-in) | HIGH |
| **Reimbursement / Expense Claim** karyawan | HIGH |
| **Exit Interview** + clearance checklist | HIGH |
| **Org Chart visual** (tree view) | MEDIUM |
| **Leave carry-forward** + encashment | MEDIUM |
| **Multi-level approval** untuk leave & claim | MEDIUM |
| **Document expiration alerts** (visa, sertifikat) | MEDIUM |
| **Skills matrix / competency tracking** | LOW |
| **Succession planning** | LOW |

---

## 2. Project Management (Taskly)

###  Sudah Ada
- Project CRUD + milestone + file upload + team invite
- Task CRUD + Kanban board (drag-drop) + calendar view
- Subtask + comment + activity log
- Bug tracker + Kanban board
- Client invitation
- Project dashboard

###  Belum Ada (Critical)
| Fitur | Prioritas |
|---|---|
| **Gantt chart** — timeline visual project | **CRITICAL** |
| **Task dependencies** — predecessor/successor (Finish-to-Start) | **CRITICAL** |
| **Time tracking per task** (integrasi Timesheet ke task) | **CRITICAL** |
| **Resource workload view** — siapa overload, siapa idle | HIGH |
| **Project budget vs actual cost** | HIGH |
| **Recurring tasks** | HIGH |
| **Sprint/Agile** — backlog, sprint planning, burndown chart | MEDIUM |
| **Project templates** | MEDIUM |
| **@mention notifications** di komentar | MEDIUM |
| **Invoice generation from project** (billable hours) | MEDIUM |
| **Task reminders** — email/Slack saat due soon | LOW |

---

## 3. Accounting (Account + DoubleEntry)

###  Sudah Ada
- Chart of Accounts (hierarchical)
- Journal entries + double-entry validation
- Bank accounts, bank transfers, bank transactions
- Revenue & expense tracking
- Customer payments + Vendor payments + allocations
- Debit notes + Credit notes
- Opening balances
- **Reports:** Trial Balance, General Ledger, P&L, Balance Sheet (comparative), Cash Flow
- Year-end close (balance sheet)
- AR Aging + AP Aging + Tax Summary

###  Belum Ada (Critical)
| Fitur | Prioritas |
|---|---|
| **Fixed Asset Module** — asset register, depreciation, disposal | **CRITICAL** |
| **Multi-currency** — exchange rate, revaluation | **CRITICAL** |
| **Bank reconciliation full workflow** — import statement CSV, auto-match | **CRITICAL** |
| **Tax engine Indonesia** — PPN Masukan/Keluaran, PPh 23, e-Faktur | **CRITICAL** |
| **Budget vs Actual** — integrasi BudgetPlanner ke COA | HIGH |
| **Period locking** — prevent posting di periode closed | HIGH |
| **Audit trail** — semua journal entry tercatat who/when | HIGH |
| **Dunning letter** — surat tagihan otomatis ke customer | MEDIUM |
| **Payment terms auto-calc** — due date dari invoice date | MEDIUM |
| **Segregation of duties** — maker-checker approval journal | MEDIUM |
| **Financial ratio analysis** — dashboard CFO | LOW |

---

## 4. CRM (Lead Management)

###  Sudah Ada
- Pipeline + Lead Stages + Deal Stages (drag-drop)
- Leads CRUD + assignment ke user/produk/source
- Deals CRUD + client assignment
- Emails, calls, discussions, files, tasks (per lead & deal)
- Labels + Sources
- Activity log
- Lead-to-Deal conversion
- CRM Dashboard + Reports

###  Belum Ada (Critical)
| Fitur | Prioritas |
|---|---|
| **Email integration (IMAP/SMTP)** — baca & kirim email dari CRM | **CRITICAL** |
| **Email templates + sequences** — automation follow-up | **CRITICAL** |
| **Lead scoring** — hot/warm/cold auto-classification | HIGH |
| **Deal forecasting** — weighted pipeline, probability % | HIGH |
| **Deal-to-Invoice conversion** — won deal → Sales Invoice | HIGH |
| **Web-to-lead form** — form publik → lead otomatis | HIGH |
| **Duplicate detection** — cegah lead ganda | MEDIUM |
| **Bulk import CSV** — import leads | MEDIUM |
| **Lead source ROI analysis** | MEDIUM |
| **Customer 360° view** — gabung leads+deals+invoices+support | LOW |
| **Meeting scheduler** — booking link | LOW |

---

## 5. POS (Point of Sale)

###  Sudah Ada
- POS order CRUD + product selection
- POS payment recording
- Barcode generation + print
- Sales report, Products report, Customers report

###  Belum Ada (Critical)
| Fitur | Prioritas |
|---|---|
| **Barcode scanner support** — scan input, bukan cuma generate | **CRITICAL** |
| **Receipt printer** — ESC/POS thermal printer | **CRITICAL** |
| **Cash drawer management** — open/close, cash count, float | **CRITICAL** |
| **Shift open/close** — cashier shift reconciliation | **CRITICAL** |
| **Split payment** — cash + card + QRIS | HIGH |
| **Discount per item + per transaction** | HIGH |
| **Refund / Void transaction** | HIGH |
| **Table management** — floor plan restoran | HIGH |
| **Kitchen printing** — order ticket ke dapur | HIGH |
| **Offline mode** — tetap jalan tanpa internet | MEDIUM |
| **Customer display (pole display)** | MEDIUM |
| **Loyalty points integration** | MEDIUM |
| **Z-Report / X-Report** — daily closing | LOW |

---

## 6. Sales Module

###  Sudah Ada
- **Sales Invoice:** CRUD, post, print, paid/balance tracking, overdue detection
- **Sales Return:** CRUD, approve, complete
- **Sales Proposal:** CRUD, send email, accept/reject, convert to invoice
- **Sales Quotation:** CRUD, accept/reject, convert to invoice
- **Credit Notes:** approve, show

###  Belum Ada (Critical)
| Fitur | Prioritas |
|---|---|
| **Sales Order** — konfirmasi sebelum delivery (Quotation→SO→Invoice) | **CRITICAL** |
| **Delivery Order / Packing Slip** | **CRITICAL** |
| **Partial delivery tracking** — kirim sebagian, backorder sisanya | **CRITICAL** |
| **Price list per customer** — harga khusus per customer/group | HIGH |
| **Volume/tier pricing** — diskon otomatis berdasarkan quantity | HIGH |
| **Salesperson assignment + commission** | HIGH |
| **Recurring invoices** — subscription/retainer billing | HIGH |
| **Approval workflow** — diskon besar / harga khusus perlu approve | MEDIUM |
| **Customer portal** — lihat invoice, bayar online, download PDF | MEDIUM |
| **Proforma invoice** | MEDIUM |
| **Sales margin report per invoice** | MEDIUM |
| **Drop-ship support** | LOW |

---

## 7. Purchase Module

###  Sudah Ada
- **Purchase Invoice:** CRUD, post, print, paid/balance tracking
- **Purchase Return:** CRUD, approve, complete
- **Debit Notes:** approve, show
- **Vendor Payments:** create, allocate to invoices

###  Belum Ada (Critical)
| Fitur | Prioritas |
|---|---|
| **Purchase Requisition (PR)** — permintaan internal sebelum order | **CRITICAL** |
| **Purchase Order (PO)** — dokumen order formal ke vendor | **CRITICAL** |
| **Goods Receipt Note (GRN)** — penerimaan barang + inspeksi | **CRITICAL** |
| **3-Way Matching** — PO vs GRN vs Invoice | **CRITICAL** |
| **RFQ (Request for Quotation)** — minta penawaran multi-vendor | HIGH |
| **Vendor price list + comparison** | HIGH |
| **Vendor evaluation / scorecard** | HIGH |
| **Landed cost calculation** — freight, insurance, customs allocation | HIGH |
| **Auto PO from reorder level** — generate PO otomatis saat stok minimum | MEDIUM |
| **Purchase approval workflow** | MEDIUM |
| **PR → PO conversion** | MEDIUM |

---

## 8. Product & Service (Inventory)

###  Sudah Ada
- Product/Service CRUD (sku, name, type, price, unit, tax, category)
- Product categories + Tax rates + Units
- Warehouse stock (quantity per warehouse)
- Stock transfers between warehouses

###  Belum Ada (Critical)
| Fitur | Prioritas |
|---|---|
| **Product variants** — size, color, variant SKU & harga | **CRITICAL** |
| **Serial number / Batch tracking** — wajib untuk warranty & recall | **CRITICAL** |
| **Expiry date tracking** — FEFO/FIFO | **CRITICAL** |
| **Inventory costing** — FIFO, LIFO, Average (saat ini tidak ada) | **CRITICAL** |
| **Stock opname / Physical count** — adjustment + approval | **CRITICAL** |
| **Minimum stock alert + reorder point** | HIGH |
| **Barcode field on product** + scan saat receive/ship | HIGH |
| **Unit conversion** — 1 box = 12 pcs (purchase UOM vs sales UOM) | HIGH |
| **Bill of Materials (BOM)** — untuk manufacturing/kit | HIGH |
| **Multiple price levels** — wholesale, retail, distributor | MEDIUM |
| **Stock aging report** | MEDIUM |
| **Bin/location dalam warehouse** | MEDIUM |
| **Landed cost allocation** ke item | MEDIUM |

---

## 9. Cross-Cutting / System-Wide Gaps

| Fitur | Status Saat Ini | Prioritas |
|---|---|---|
| **WhatsApp Gateway** — notifikasi, OTP, chat customer | Tidak ada | **CRITICAL** |
| **Payment Gateway Lokal** — Midtrans, Xendit | Hanya Stripe/PayPal | **CRITICAL** |
| **Multi-Tenant Management** — DB per tenant, isolated | Partial (created_by) | **CRITICAL** |
| **Import/Export Excel** — bulk import master data | Hampir tidak ada | **CRITICAL** |
| **System-wide Audit Trail** — semua aksi tercatat | Hanya di Taskly & Lead | HIGH |
| **Executive Dashboard** — KPI gabungan semua modul | Tidak ada | HIGH |
| **Notification Center** — email + WA + push + in-app | Parsial | HIGH |
| **Workflow Engine** — visual approval builder | Tidak ada | HIGH |
| **Global Search** — cari di semua modul | Tidak ada | MEDIUM |
| **Mobile App / PWA** | Tidak ada | MEDIUM |
| **Database Backup Scheduler** | Tidak ada | MEDIUM |
| **Customer / Vendor Portal** — self-service | Tidak ada | MEDIUM |
| **Role-based Dashboard Widget** — beda role beda tampilan | Tidak ada | MEDIUM |
| **Scheduler / Cron Jobs** — overdue escalation, reminder | Belum terkonfigurasi | MEDIUM |

---

##   Rekomendasi Urutan Implementasi

### Fase 1 — Operasional Dasar (Month 1-2)
1. **Sales Order + Purchase Order** — formal document flow
2. **Delivery Order + GRN** — goods movement
3. **Product Variants + Serial/Batch** — inventory integrity
4. **Inventory Costing (FIFO/Average)** — accurate COGS

### Fase 2 — Finance & Compliance (Month 2-3)
5. **Fixed Asset Module** — depreciation, disposal
6. **Multi-Currency** — exchange rate
7. **Tax Engine Indonesia** — PPN, PPh, e-Faktur
8. **Bank Reconciliation** — statement import + auto-match

### Fase 3 — Automation & Experience (Month 3-4)
9. **Employee Self-Service Portal**
10. **Email Integration in CRM** — IMAP/SMTP
11. **POS Full Hardware** — scanner, printer, cash drawer
12. **WhatsApp Gateway**

### Fase 4 — Platform & Scale (Month 4-5)
13. **Multi-Tenant Management**
14. **Payment Gateway Lokal** — Midtrans/Xendit
15. **Import/Export Excel** — semua modul
16. **System-wide Audit Trail**
17. **Executive Dashboard**

### Fase 5 — Advanced (Month 5-6)
18. **Gantt Chart + Task Dependencies**
19. **Workflow Engine**
20. **Mobile PWA**
21. **Customer/Vendor Portal**
22. **Global Search**

---

> **Total gap teridentifikasi: ~120 fitur** di 8 modul inti + cross-cutting.
> **Estimasi effort: 4-6 bulan** untuk mencapai ERP produksi-ready.
