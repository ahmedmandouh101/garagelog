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

## Next delivery: complete customer intake and receipts

1. Workshop-created customer intake by phone, followed by verified account claiming.
2. Workshop directory picker and a visible list of active/revoked vehicle grants.
3. Printable individual service receipts and workshop service history.
4. Password recovery and verified identity/contact methods.
5. Consent-based messaging integration and delivery status.

Acceptance: workshop staff can register a customer and vehicle, record a service,
deliver the receipt and arrange the next service without developer assistance.

## Before public production

- Self-registered mechanics have no workshop access until an invitation is accepted or an admin assigns them.
- Owner consent is required for service creation and updates. Mechanics can still read their own historical service records after consent revocation; this does not expose other workshops' history.
- Establish archive/audit and ownership-transfer policies; admin service deletion remains destructive.
- Configure real mail delivery, HTTPS, backups, monitoring and scheduler infrastructure.
- Separate public staff profile fields from private email/phone fields.
- Implement password recovery, account verification and token/session lifecycle.
- Add mileage reminders and normalize service types before relying on supersession rules.
- Define billing, tenant limits, support and onboarding after pilot interviews.
- Replace demo administrator credentials before any public deployment.

## Pilot acceptance

- Two workshops, 50 genuine service entries.
- Target routine service entry in under two minutes.
- At least one workshop agrees to continue on a paid plan.
- Measure weekly active workshops, recurring use, support time, message costs and retention.

Pricing discussed during review is a hypothesis, not validated willingness to pay.
