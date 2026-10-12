# PostgreSQL Setup & Integration Guide — MicroTool Hub

**MicroTool Hub** provides native, first-class support for **PostgreSQL 14+** (including cloud serverless providers like **Neon**, **Supabase**, **Render**, **Railway**, and **AWS RDS**), alongside MySQL 8 and SQLite.

---

## 1. Prerequisites (PHP Extensions)

Ensure the `pdo_pgsql` and `pgsql` extensions are enabled in your `php.ini`:

```ini
extension=pdo_pgsql
extension=pgsql
```

Verify with:
```bash
php -m | grep pgsql
```

---

## 2. Environment Configuration (`.env`)

You can connect to PostgreSQL using either individual parameters or a single cloud connection string (`DATABASE_URL`).

### Option A: Standard Credentials (Local or Dedicated Host)
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=microtool_hub
DB_USERNAME=postgres
DB_PASSWORD=your_secure_password
DB_SCHEMA=public
DB_SSLMODE=prefer
```

### Option B: Cloud Connection String (Neon, Supabase, Render, Railway, AWS RDS)
MicroTool Hub's `config/database.php` supports `DATABASE_URL` or `DB_URL` out of the box:

```env
DB_CONNECTION=pgsql
DATABASE_URL=postgresql://username:password@ep-sample-pool-123456.us-east-2.aws.neon.tech/microtool_hub?sslmode=require
```

> [!TIP]
> **Neon & Supabase PgBouncer Note:**
> When running migrations (`php artisan migrate`), connect to the **direct compute endpoint** (e.g. `ep-xyz.aws.neon.tech` instead of `ep-xyz-pooler.aws.neon.tech`) to ensure uninterrupted transactional DDL execution.

---

## 3. Database Initialization & Seeding

Run the complete migration and seeder suite:

```bash
# Run migrations
php artisan migrate

# Seed initial categories, demo users, sample tools, and OAuth clients
php artisan db:seed
```

---

## 4. Migration Compatibility Highlights
All migrations in `database/migrations/` use standard Laravel schema definitions tested and validated on PostgreSQL:
- **Primary Keys**: Auto-incrementing `bigserial` columns.
- **UUIDs**: Standard indexed UUID strings.
- **Foreign Keys**: Cascading deletes and nullable constraints.
- **JSON Data**: Native PostgreSQL `JSON` columns for audit log payloads.
- **Timestamps**: PostgreSQL `timestamp without time zone` with `CURRENT_TIMESTAMP` defaults.
- **Booleans**: Native PostgreSQL `boolean` types.
