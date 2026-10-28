# Local operations

Canonical source checkout: `C:\xamppp\htdocs\garagelog`.
The Documents/GarageLog folder is not the application checkout.

## Update and verify

```powershell
php artisan migrate
php vendor/bin/pest --compact
php vendor/bin/pint --dirty --test
php artisan schedule:list
```

API collections use Laravel Resource pagination: `data`, `links`, and `meta`.
The result count is `meta.total`. Service dates and formatted distances retain
their existing API representation after correcting the swapped resource files.

## Reminder execution

```powershell
php artisan reminders:send
```

This sends real emails when a real mail transport is configured. Tests use fake mail.
The command sends synchronously so failures can be retried without marking them sent.
It includes services due today through the next seven days, excludes older services
superseded by a later entry of the same service type, and records the due date sent.
Changing that due date makes the service eligible again.

The application schedule runs at 09:00 Africa/Cairo. A running scheduler is required:
use `php artisan schedule:work` for local development, or invoke
`php artisan schedule:run` every minute from the production host's scheduler.
The schedule definition alone does not start a background process.

Use a shared persistent cache store in multi-instance deployments so reminder locks
are shared. Email acceptance and database updates are not an atomic operation:
a crash after transport acceptance but before saving can cause a duplicate retry.
Large-scale delivery needs an outbox and provider idempotency/delivery tracking.
Overdue reminders and mileage reminders are not implemented in this delivery.

No live mail send, external deployment or OS scheduled task was performed as part
of the foundation update. See ROADMAP.md for remaining production prerequisites.

## Arabic workspace and local demo

```powershell
php artisan db:seed --class=WorkshopDemoSeeder
php artisan serve --host=127.0.0.1 --port=8010
```

Open `http://127.0.0.1:8010`. Demo credentials, restricted to local/testing environments:

- Manager: `manager@garagelog.example.test`
- Owner: `owner@garagelog.example.test`
- Password for both: `GarageLogDemo2026!`

The demo seeder adds clearly labeled fictional data. It does not reset the database,
change existing account passwords, or send emails. Never run a publicly reachable
instance with these demo accounts. Authentication tokens are held in page memory;
reloading the page requires signing in again. Logging out revokes the current token.

### Workshop onboarding API

1. Admin: `PUT /api/garages/{garage}/manager` with a mechanic `user_id`.
2. Manager: `POST /api/workshop/invitations` with the employee email.
   Share the returned token privately with that employee; automatic delivery is not configured.
3. Employee: register a mechanic account, then `POST /api/workshop/invitations/accept`
   with `token`, while authenticated using the invited email.
4. Owner: add their vehicle and `POST /api/cars/{car}/garage-access/{garage}`.
5. Workshop staff: `GET /api/workshop/cars`, then record service using the existing API.
6. Owner: `DELETE /api/cars/{car}/garage-access/{garage}` to revoke future writes.

Manager metrics (`GET /api/workshop/dashboard`) distinguish labor totals from parts:
`labor_total_this_month` is the service `cost` column only, not total sales or profit.
Managers use `GET /api/workshop/staff` and `DELETE /api/workshop/staff/{user}`.
Multiple managers are allowed; the staff-removal endpoint cannot remove managers.

Customer self-registration and owner consent are working. Workshop intake for a
customer who has not registered is still planned; do not create accounts or grant
vehicle access on their behalf. See ROADMAP.md for remaining workflow gaps.
