# Membership Management: phases 1–3

Member CRUD and category/level administration have been reviewed. TPS and expanded member details are the current review checkpoint. Do not implement the dashboard or other later features without a request.

## Theme

Keep the Porsche-inspired visual direction: a dark masthead, confident typography, white content surfaces, crisp edges and restrained red accents. Keep forms clear and responsive. Preserve the existing task tutorial and its saved design backup.

## Included

- Administrator login using Laravel session authentication and login rate limiting.
- Member registration, list, profile, editing, deactivation and reactivation through editing.
- Name and membership-number search, category/level/status filtering and pagination.
- Automatically generated unique membership numbers, immutable through submitted form data.
- Required-field, email, duplicate-email and category/level assignment validation.
- Category and level CRUD, with assigned-member counts and links to filtered member lists.
- Level fee (nonnegative, two decimal places), validity in whole months and optional benefits.
- Assigned categories/levels cannot be deleted, including those assigned to inactive members. Reassign members first.
- Fees are in Philippine pesos: Basic 5,000; Silver 10,000; Gold 15,000; Platinum 25,000.
- Validity periods: Basic 12 months, Silver 24 months, Gold 36 months, Platinum 60 months. Benefits are editable but have not been invented or prefilled.
- TPS: applications, renewals, recorded payments and admin approval. Every transaction and payment has a unique generated reference.
- Transactions capture the category, level, fee, validity and benefits at creation. Later level edits do not rewrite historical terms.
- Partial payments are allowed. Overpayment, invalid payment amounts and duplicate payment references are rejected. Repeated submissions of the same payment key do not record a second payment.
- Fully paid transactions become Paid. Only Paid transactions with zero balance can be approved. Free memberships are Paid immediately without a payment record.
- Approval sets dates. An early renewal starts the day after the existing expiry; an expired renewal starts on approval. Expiry is start plus the purchased number of calendar months, minus one day, inclusive.
- Expired is calculated from an approved transaction's end date. Pending, Paid and Cancelled do not become Expired automatically.
- Only Pending transactions without payments can be cancelled. Refunds and reversing payments are not implemented.
- This records money already received; it does not initiate charges with payment providers.
- Member profiles include assigned benefits, approved-term benefits, registration/join/expiry dates and paginated transaction/payment history.

Administrators can deactivate members to retain their records or permanently delete members without transaction history after explicitly checking the confirmation box. Transaction-linked members, categories and levels cannot be deleted. Both actions are administrator-only.

## Run

Start MySQL in XAMPP, then from this project run:

```powershell
php artisan migrate
php artisan membership:admin your-email@example.com --name="Your name"
php artisan serve
```

The administrator command asks privately for a password and confirmation. It never prints the password and will not change an existing account. Open `/members` and sign in. Do not use a public demo password.

## Verification

```powershell
php artisan test --compact
```

Tests use a separate in-memory SQLite database. They do not delete records from the configured MySQL database.

## Later phases (not implemented)

Printable receipts and dashboard reporting.

## TPS demonstration

1. Open Transactions → New transaction and choose an active member and Application.
2. Open the created transaction and record payment (or installments) against its balance.
3. Once Paid, choose Approve membership. Check the dates and member profile.
4. Create a Renewal for that member and repeat. Confirm validity is extended.
5. Use Transactions status filters and Payments to inspect the ledger.

Sample JJK members remain as supplied; development tests do not create live payments or transactions for them.
