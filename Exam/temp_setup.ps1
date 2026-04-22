# Try creating the DB with no password (just in case they have trust enabled)
& 'C:\Program Files\PostgreSQL\16\bin\createdb.exe' -U postgres Exam_DB 2>$null
if ($?) {
    Write-Host "Database created with version 16! Proceeding to restore..."
    & 'C:\Program Files\PostgreSQL\16\bin\pg_restore.exe' -U postgres -d Exam_DB 'Exam_DB.sql'
} else {
    Write-Host "Version 16 failed. Trying Version 14 with no password..."
    & 'C:\Program Files\PostgreSQL\14\bin\createdb.exe' -U postgres Exam_DB 2>$null
    if ($?) {
        Write-Host "Database created with version 14! Proceeding to restore..."
        & 'C:\Program Files\PostgreSQL\14\bin\pg_restore.exe' -U postgres -d Exam_DB 'Exam_DB.sql'
    } else {
        Write-Host "Both failed. Password required or some other issue."
        exit 1
    }
}
