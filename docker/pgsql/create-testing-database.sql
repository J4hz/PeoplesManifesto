-- The test suite runs against its own database, with pgvector available.
SELECT 'CREATE DATABASE manifesto_test'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'manifesto_test')\gexec

\c manifesto_test
CREATE EXTENSION IF NOT EXISTS vector;
