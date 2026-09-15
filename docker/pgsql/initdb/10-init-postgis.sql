-- Enable PostGIS on the primary application database (POSTGRES_DB).
CREATE EXTENSION IF NOT EXISTS postgis;

-- Create Laravel's dedicated PHPUnit "testing" database (mirrors Sail's
-- stock create-testing-database.sql) and enable PostGIS there too, so
-- future geospatial feature tests don't need to revisit this file.
SELECT 'CREATE DATABASE testing'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'testing')\gexec

\c testing
CREATE EXTENSION IF NOT EXISTS postgis;
