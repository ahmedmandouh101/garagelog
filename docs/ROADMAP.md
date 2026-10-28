# GarageLog delivery plan

## Product direction

Pilot a mobile-friendly Arabic workshop subscription: customer intake, vehicle history,
service entry, service receipt, and maintenance follow-up. Validate with two workshops
before investing in native mobile applications or a marketplace.

## Foundation completed — 2026-10-28

- Correct the swapped service/part API resources.
- Scope nested service endpoints to their vehicle.
- Restrict service updates to the recording mechanic in their current workshop.
- Require workshop membership when creating a service.
- Protect review creation and reading against unrelated vehicles/users.
- Save service and replacement parts in database transactions.
- Refuse car/workshop deletion when service history exists.
- Limit authentication requests to 10/minute per IP.
- Schedule reminders at 09:00 Africa/Cairo; catch upcoming dates within seven days.
- Track sent due dates, lock overlapping record sends, and skip superseded services
  with the same service type. Include the actual due service in the email.
- Select latest vehicle service by service date, with ID as a tie breaker.
- Add regression coverage for access boundaries, rollback, reminders and rate limits.

## Workshop access and Arabic workspace completed — 2026-10-28

- Admin-appointed workshop managers (mechanic accounts with a manager flag).
- Email-targeted invitations with hashed tokens, two-day expiry and one-time acceptance.
- Staff removal without deleting service history; manager actions limited to their own workshop.
- Owner-granted vehicle access; revocation blocks new service entries and edits.
- Workshop vehicle list contains only active grants from the current vehicle owner.
- Arabic RTL browser workspace: login/registration, owner vehicles and history, permissions,
  service entry with parts, manager metrics, staff invitations and membership removal.
- Public workshop profiles no longer expose staff email or phone.
- Local-only demo seeder and end-to-end browser verification of manager service entry.

## Customer intake and receipts completed — 2026-10-28

- Private workshop customer intake with phone, vehicle details and notes, isolated by workshop.
- No user account, canonical vehicle, or service permission is created during intake.
- Seven-day hashed linking codes with rotation, expiry and one-time acceptance.
- Owner previews the workshop name and matching plate before explicitly granting access.
- Owners link only vehicles already in their accounts; plate matching does not verify legal ownership or phone identity.
- Workshop directory search replaces manual workshop IDs in owner permissions.
- Owners can list active/revoked grants and revoke each workshop directly.
- Paginated workshop service history and authenticated individual service receipts.
- Receipts contain labor, parts, quantities, totals and next service details, with a print/PDF action.
- Receipts are service summaries, not proof of payment or tax invoices.

## Account security and service integrity completed — 2026-10-28 (main)

- Password recovery with expiring, single-use broker tokens and an Arabic reset form.
- Reset completion revokes all personal access tokens and database sessions.
- Recovery requests use a generic response for registered/unregistered addresses.
- Signed email confirmation links expire after one hour; resends are rate limited.
- Unverified users can sign in, inspect their own account, request verification and log out;
  protected workshop/vehicle APIs require verified email.
- New login/registration tokens expire after 24 hours.
- Service dates cannot be in the future; upcoming date/mileage must follow the service.
- Chronological odometer validation supports backdated service entries without rolling back current mileage.
- Service writes and owner odometer updates use vehicle-row locking and transactions.
- Monetary inputs reject more than two decimal places and out-of-range storage values.
- Email verification confirms access to the mailbox, not phone identity or legal vehicle ownership.

## Next delivery: contact verification, service types and pilot readiness

1. Phone verification and a policy for vehicle ownership verification.
2. Customer intake corrections, duplicate handling, consent audit and retention rules.
3. Normalized service types, mileage validation and mileage reminders.
4. Consent-based messaging integration and delivery status.
5. Pilot onboarding, support metrics and subscription experiments.

Acceptance: workshop staff can register a customer and vehicle, record a service,
deliver the receipt and arrange the next service without developer assistance.

## Before public production

- Self-registered mechanics have no workshop access until an invitation is accepted or an admin assigns them.
- Owner consent is required for service creation and updates. Mechanics can still read their own historical service records after consent revocation; this does not expose other workshops' history.
- Establish archive/audit and ownership-transfer policies; admin service deletion remains destructive.
- Configure real mail delivery, HTTPS, backups, monitoring and scheduler infrastructure.
- Separate public staff profile fields from private email/phone fields.
- Configure and test actual email delivery; align APP_URL with the real application origin. Add phone verification and lifecycle controls for legacy tokens.
- Add mileage reminders and normalize service types before relying on supersession rules.
- Define billing, tenant limits, support and onboarding after pilot interviews.
- Replace demo administrator credentials before any public deployment.

## Pilot acceptance

- Two workshops, 50 genuine service entries.
- Target routine service entry in under two minutes.
- At least one workshop agrees to continue on a paid plan.
- Measure weekly active workshops, recurring use, support time, message costs and retention.

Pricing discussed during review is a hypothesis, not validated willingness to pay.
