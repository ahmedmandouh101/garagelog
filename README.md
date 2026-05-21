# GarageLog API 🚗

> Your car's memory — because your mechanic doesn't have one.

A REST API for a car maintenance tracking platform built for the Egyptian market. Car owners can track their full service history, get reminders, and mechanics can log services professionally.

---

## The Problem It Solves

In Egypt, when you go to a garage:
- No record of what was done to your car
- No reminders for next oil change or service
- You can't prove your car's history when selling it
- Zero trust between car owner and mechanic

**GarageLog fixes all of that.**

---

## Tech Stack

- **Laravel 11** — PHP Framework
- **MySQL** — Database
- **Laravel Sanctum** — API Authentication
- **PHPUnit** — Testing

---

## Features

- JWT-less token authentication with Sanctum
- Role-based access control (Owner / Mechanic / Admin)
- Multi-car management per owner
- Full service history per car
- Parts tracking per service
- Next service reminders (date + mileage)
- Review and rating system for mechanics
- Database notifications
- Filters and search on all endpoints
- 16 automated tests

---

## API Endpoints

### Auth
| Method | Endpoint | Access |
|--------|----------|--------|
| POST | `/api/register` | Public |
| POST | `/api/login` | Public |
| POST | `/api/logout` | Authenticated |

### Garages
| Method | Endpoint | Access |
|--------|----------|--------|
| GET | `/api/garages` | Public |
| GET | `/api/garages/{id}` | Public |
| POST | `/api/garages` | Admin |
| PUT | `/api/garages/{id}` | Admin |
| DELETE | `/api/garages/{id}` | Admin |

### Cars
| Method | Endpoint | Access |
|--------|----------|--------|
| GET | `/api/cars` | Owner |
| POST | `/api/cars` | Owner |
| GET | `/api/cars/{id}` | Owner |
| PUT | `/api/cars/{id}` | Owner |
| DELETE | `/api/cars/{id}` | Owner |

### Service Records
| Method | Endpoint | Access |
|--------|----------|--------|
| GET | `/api/cars/{car}/service-records` | Owner |
| POST | `/api/cars/{car}/service-records` | Mechanic |
| GET | `/api/cars/{car}/service-records/{id}` | Owner/Mechanic |
| PUT | `/api/cars/{car}/service-records/{id}` | Mechanic |
| DELETE | `/api/cars/{car}/service-records/{id}` | Admin |

### Reviews
| Method | Endpoint | Access |
|--------|----------|--------|
| POST | `/api/cars/{car}/service-records/{id}/reviews` | Owner |
| GET | `/api/cars/{car}/service-records/{id}/reviews` | Authenticated |
| GET | `/api/mechanic/reviews` | Mechanic |

### Notifications
| Method | Endpoint | Access |
|--------|----------|--------|
| GET | `/api/notifications` | Authenticated |
| GET | `/api/notifications/unread` | Authenticated |
| POST | `/api/notifications/{id}/read` | Authenticated |
| POST | `/api/notifications/read-all` | Authenticated |

---

## Installation

```bash
git clone https://github.com/YOUR_USERNAME/garagelog.git
cd garagelog
composer install
cp .env.example .env
php artisan key:generate
```

Configure your `.env` database settings then:

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

Default admin credentials:
```
Email: admin@garagelog.com
Password: password123
```

---

## Running Tests

```bash
php artisan test
```

---

## Database Design

```
users
  └── cars
        └── service_records
                  ├── service_parts
                  └── reviews

garages
  └── mechanics (users)
  └── service_records
```

---

Built by Ahmed Mandouh
