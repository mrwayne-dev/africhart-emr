# AfriChart EMR — User Guide

A plain-language guide to using AfriChart EMR day to day. It is written for the people who
will actually use the system — the front desk, nurses, doctors, and the clinic owner — not
for developers. Read the section for your role; the rest is there if you're curious how the
whole clinic fits together.

---

## Table of contents

1. [What AfriChart EMR is](#1-what-africhart-emr-is)
2. [Getting access (everyone)](#2-getting-access-everyone)
3. [Things that work on every screen](#3-things-that-work-on-every-screen)
4. [The four roles at a glance](#4-the-four-roles-at-a-glance)
5. [Receptionist — front desk & billing](#5-receptionist--front-desk--billing)
6. [Nurse — vitals & the queue](#6-nurse--vitals--the-queue)
7. [Doctor — consultations & prescriptions](#7-doctor--consultations--prescriptions)
8. [Administrator — oversight & everything](#8-administrator--oversight--everything)
9. [How a single patient flows through the clinic](#9-how-a-single-patient-flows-through-the-clinic)
10. [Common questions](#10-common-questions)

---

## 1. What AfriChart EMR is

AfriChart EMR is the clinic's electronic medical records system. It replaces paper files and
scattered notebooks with one shared place where:

- the **front desk** registers patients and takes payments,
- **nurses** record vitals,
- **doctors** write consultations and prescriptions, and
- the **owner/administrator** sees everything that's happening and the money coming in.

Everyone works in the same system at the same time, and the screen updates itself as
colleagues make changes — so a nurse doesn't have to keep asking the front desk "who just
arrived?"

---

## 2. Getting access (everyone)

### Opening the system

Open the clinic's AfriChart web address in any browser (on a computer, tablet, or phone).
You'll land on the **login page**.

### Creating your account

New staff create their own account from the **"Create an account"** link on the login page:

1. Choose your role tab — **Doctor**, **Nurse**, **Receptionist**, or **Admin**.
2. Enter your name, email, and a password (typed twice to confirm).
3. Enter the **invite code** for your role. The administrator gives you this code — it's what
   tells the system you're allowed to register as, say, a doctor. Without the right code you
   can't create that kind of account.
4. Submit. The system creates your account and sends a verification code to your email.

> **Why invite codes?** They stop just anyone from signing up as a doctor or admin. Each role
> has its own code, kept private by the clinic.

### Verifying your email

After registering you'll see a screen asking for a **6-digit code**. Check your email inbox,
type the code in, and you're verified. The code expires after 10 minutes — if it does, click
**Resend code** for a new one.

### Logging in and forgetting your password

- **Log in** with your email and password. You can tap the little eye icon to reveal what
  you typed.
- Forgot your password? Click **"Forgot password?"**, enter your email, and follow the reset
  link the system emails you.

### What you'll see after logging in

Everyone lands on a **dashboard**, but each role's dashboard is different — it shows the tools
and information that role needs, and hides the rest. The menu down the side only lists the
pages you're allowed to use.

---

## 3. Things that work on every screen

These behave the same no matter your role:

- **Live updates — no need to refresh.** Pages with a small pulsing **"● Live"** badge update
  themselves every few seconds. When the front desk checks a patient in, the nurse's queue
  shows it on its own. (Two exceptions, on purpose: the consultation page you're *writing*
  won't overwrite your typing — instead it shows a small "this was updated — Refresh" note so
  you decide when to reload. And the admin's charts refresh when you reload the page.)
- **Pop-up confirmations (toasts).** After you save, pay, complete, etc., a small message
  appears top-right confirming it worked (or telling you what went wrong).
- **Search and filter.** List pages (patients, consultations, invoices) have a search box and
  filters. The web address updates as you filter, so you can bookmark or share a filtered view.
- **Works on phones and tablets.** The layout adapts to small screens — the side menu
  collapses and tables scroll sideways.
- **Print & PDF.** Invoices can be printed or downloaded as a clean PDF receipt.
- **Logging out** asks you to confirm, so you don't lose your place by accident.

---

## 4. The four roles at a glance

| You can… | Receptionist | Nurse | Doctor | Admin |
|---|:---:|:---:|:---:|:---:|
| Register & edit patients | ✅ | ✅ | ✅ | ✅ |
| Check patients into the queue | ✅ | ✅ | — | ✅ |
| Assign a patient to a doctor | ✅ | ✅ | — | ✅ |
| Cancel a queue entry | ✅ | — | — | ✅ |
| Record vitals | — | ✅ | ✅ | ✅ |
| Write consultations & prescriptions | — | — | ✅ | ✅ |
| Complete a consultation | — | — | ✅ | ✅ |
| Generate & manage invoices, take payment | ✅ | — | view only | ✅ |
| See clinic charts, audit log, exports | — | — | — | ✅ |

A blank means that tool simply won't appear for you.

---

## 5. Receptionist — front desk & billing

Your dashboard is the front-desk command centre: today's numbers, the patient queue, and your
**Ready to Invoice** worklist.

### Register a new patient

1. Go to **Patients** → **Register Patient** (a form opens without leaving the page).
2. Fill in name, date of birth, phone, blood group, and any allergies.
3. Save. The system assigns a unique patient ID (e.g. `ACH-20260614-0001`) and confirms with
   a pop-up. You can edit a patient any time from their row.

### Check a patient into today's queue

1. Go to **Queue** → **Check In Patient**.
2. Pick the patient, add a short reason for the visit, and (optionally) assign a doctor.
3. Save. They get the next queue number and appear on everyone's live queue immediately.

You can **reassign** a patient to a different doctor, or **cancel** a queue entry, right from
the queue table.

### Take payment (billing)

This is the key front-desk job after a doctor finishes:

1. The moment a doctor **completes** a consultation, it appears in your **"Ready to Invoice"**
   list on the dashboard (patient, doctor, and when it finished — no clinical details).
2. Click **Generate Invoice**. The system creates a draft pre-filled with the consultation fee
   and a line for each prescribed medicine (priced at ₦0, because prices vary).
3. On the invoice, type the real prices into the table — **the total adds up as you type**. You
   can add extra lines (a lab test, dressings, etc.).
4. When the patient pays, click **Mark as Paid** and choose the method (e.g. Cash). The status
   becomes **Paid**.
5. Hand them a receipt: **Print** or **Download PDF**.

> You don't see doctors' clinical notes or diagnoses — billing only needs the patient, the
> fee, and the prescription list. That's by design.

---

## 6. Nurse — vitals & the queue

Your dashboard centres on the live patient queue so you can see who's waiting.

### Check a patient in (if you handle intake)

Same as the front desk: **Queue** → **Check In Patient**, choose the patient and reason, and
optionally assign a doctor.

### Record vitals

1. Open the patient's **consultation** (from the queue or the Consultations list).
2. In the **Vitals** box, click **Record** (or **Update**).
3. Enter temperature, blood pressure, pulse, weight, and height. The system works out **BMI**
   for you. Add a note if needed, and save.

The doctor sees the vitals you entered on their copy of the consultation within seconds — no
need to tell them in person.

> You can see consultations so you have clinical context, but writing the notes, diagnosis, and
> prescriptions is the doctor's job.

---

## 7. Doctor — consultations & prescriptions

Your dashboard shows **your** queue — the patients assigned to you — kept live.

### Start a consultation

1. From your queue (or **Consultations** → **New Consultation**), choose the patient.
2. Enter the chief complaint to start. The system gives the visit an ID and marks the patient
   as "in consultation."

### Write up the visit

On the consultation page you'll find everything in one place:

- **Patient & vitals** at the top (vitals are usually filled in by the nurse).
- **Clinical notes** — chief complaint, notes, **diagnosis**, and plan. Click **Edit** to write
  them.
- **Prescriptions** — add one or more medicines. Start typing a drug name and the system
  suggests common ones and fills in a typical dosage/frequency. Click **Add another** for more
  rows, then **Save Prescriptions** (they all save at once).

> While you're typing, the page won't refresh under you. If a colleague changes something, a
> small "this was updated — Refresh" note appears so you can reload when *you're* ready.

### Finish

When you're done, click **Complete Consultation**. This closes the visit, updates the queue,
and sends it to the front desk's billing list. (You can view invoices, but the front desk
prices and takes payment.)

---

## 8. Administrator — oversight & everything

You can do everything the other roles can, plus see the whole clinic.

### Your dashboard

- **Six stat cards** — total patients, registered today, this week, today's consultations,
  pending invoices, and revenue this month.
- **Charts** — patient registrations over the last 30 days, revenue over the last 6 months,
  and consultations by status.
- **Ready to Invoice** — the same billing worklist the front desk uses.
- **Recent Activity** — a running feed of what staff have done today.

### Oversight tools (admin only)

- **Audit Log** — a searchable record of every create/update/delete: who did it, when, and
  what changed. Filter by staff member or record type. Nothing is lost or untracked.
- **Export** — one click downloads patients, consultations, or invoices as a spreadsheet (CSV)
  for accounting or reporting.

### Managing staff access

Give each new staff member the **invite code** for their role (kept in the system's secure
settings). Anyone with a code can self-register; you can watch new registrations appear in the
activity feed and audit log.

---

## 9. How a single patient flows through the clinic

A typical visit, showing how the roles hand off to each other:

1. **Front desk** registers the patient (if new) and **checks them in** → they get a queue
   number.
2. **Nurse** records **vitals**.
3. **Doctor** opens the consultation, writes **notes + diagnosis**, adds **prescriptions**, and
   clicks **Complete**.
4. **Front desk** sees the visit in **Ready to Invoice**, generates the bill, prices it, takes
   payment, and prints a **receipt**.
5. Anyone viewing the patient's record sees the whole journey on their **Timeline**:
   *Registered → Consultation → Invoice (Paid)*.
6. The **admin's** dashboard rolls the day's activity into stats and charts.

Each step is owned by the right person, and the screen updates for everyone as it happens.

---

## 10. Common questions

**Do I need to refresh to see new patients/changes?**
No. Screens with the **"● Live"** badge update on their own every few seconds.

**I made an account but can't get in.**
You probably haven't entered the 6-digit email verification code yet. Check your email; use
**Resend code** if it expired.

**I don't see a button a colleague has.**
The system only shows the tools for your role. For example, only the front desk and admin can
take payments; only doctors and admin write consultations.

**Why can't the front desk see diagnoses?**
On purpose — billing only needs the patient and the charges, not clinical details. It keeps
patient information private to the people treating them.

**Can I use it on my phone?**
Yes. The layout adapts to phones and tablets.

**Something went wrong / I got an error message.**
The red pop-up usually says what happened (e.g. a required field was empty). If something looks
broken, note what you were doing and tell your administrator.

---

*AfriChart EMR — one clean line from the front door to a paid bill, with every step owned by
the right person.*
