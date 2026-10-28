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
