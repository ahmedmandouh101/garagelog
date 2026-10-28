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

## Next delivery: workshop workflow

1. Workshop manager role and explicit employee invitations/approval.
2. Customer intake by phone; vehicle ownership consent and workshop access grants.
3. Arabic RTL responsive workshop dashboard and service entry.
4. Read-only customer history with authenticated or expiring access.
5. Printable service receipt: labor, parts, total, workshop and next due date.
6. Consent-based messaging integration and delivery status.

Acceptance: workshop staff can register a customer and vehicle, record a service,
deliver the receipt and arrange the next service without developer assistance.

## Before public production

- Mechanic self-registration currently remains available; replace it with approval/invitations.
- Current service creation requires workshop membership but does not yet require vehicle-owner consent.
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
