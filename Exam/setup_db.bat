@echo off
set PGPASSWORD=postgres
echo Creating User and Database...
"C:\Program Files\PostgreSQL\14\bin\psql.exe" -U postgres -c "CREATE USER tybcs WITH PASSWORD 'msgcs';"
"C:\Program Files\PostgreSQL\14\bin\psql.exe" -U postgres -c "CREATE DATABASE \"Exam_DB\" OWNER tybcs;"
echo.
echo Restoring Database from Exam_DB.sql...
"C:\Program Files\PostgreSQL\14\bin\pg_restore.exe" -U postgres -d Exam_DB "Exam_DB.sql"
echo.
echo Setup Complete!
pause
