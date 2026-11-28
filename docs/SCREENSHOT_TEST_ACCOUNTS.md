# Screenshot Test Accounts and Temporary Data

Use these accounts only in a local or test database. The passwords are intentionally temporary and should not be used in a production environment.

## Login information

| Role | Name | Email | Password |
|---|---|---|---|
| System Administrator | Temporary Administrator | `temporary.admin@nemsu.edu.ph` | `TempPass!2026` |
| Adviser | Maria Santos | `maria.santos@nemsu.edu.ph` | `TempPass!2026` |
| Coordinator | Juan Dela Cruz | `juan.delacruz@nemsu.edu.ph` | `TempPass!2026` |
| Company Supervisor | Carlos Mendoza | `carlos.mendoza@nemsu.edu.ph` | `TempPass!2026` |
| Student | Ana Reyes | `ana.reyes@nemsu.edu.ph` | `TempPass!2026` |
| Student | Mark Villanueva | `mark.villanueva@nemsu.edu.ph` | `TempPass!2026` |
| Student | Liza Flores | `liza.flores@nemsu.edu.ph` | `TempPass!2026` |


## Preparing the database

After the migrations have been applied, run from the project root:

```powershell
& 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe' database\seed_screenshot_data.php
```

The seeder creates or refreshes the temporary accounts and sample records. It can be rerun safely. Temporary account passwords are reset to `TempPass!2026`, and their role/status values are refreshed.

## Temporary screenshot data

| Area | Seeded data |
|---|---|
| Academic program | BSCS — Bachelor of Science in Computer Science |
| Companies | Mindanao Digital Solutions; Agusan Technology Hub |
| Positions | Web Developer Intern; IT Support Intern |
| Skills | PHP Programming; MySQL Database; HTML/CSS |
| Requirement types | MOA; Resume; Medical Certificate |
| Student profiles | 2026-0001 Ana Reyes; 2026-0002 Mark Villanueva; 2026-0003 Liza Flores |
| Adviser assignments | Maria Santos is assigned to all three students |
| Supervisor association | Carlos Mendoza is linked to Mindanao Digital Solutions |
| Placements | Ana Reyes — active; Mark Villanueva — pending; Liza Flores — completed |
| Attendance | One verified attendance record for Ana Reyes |
| Evaluation | One 5/5 evaluation for Liza Flores |
| Requirement | One approved MOA submission for Ana Reyes |
| Notification | One unread placement approval notification for Ana Reyes |

### Company Supervisor temporary data

Carlos Mendoza is linked to **Mindanao Digital Solutions** and can capture screenshots of:

- Active placement for Ana Reyes, with pending attendance submitted on November 25, 2026.
- Verified attendance for Ana Reyes on November 24, 2026: 8.00 hours.
- Completed placement for Liza Flores, with final verified attendance and an existing 5/5 evaluation.
- Placement evaluation forms for both active and completed linked placements.

## Suggested screenshots

- **System Administrator:** sign in and capture dashboard, user accounts, student details, companies, positions, advisers, and reports.
- **Adviser:** capture the adviser dashboard and assigned-student pages.
- **Coordinator:** capture placement management, requirement review, and reports.
- **Company Supervisor:** capture the workflow page with the verified attendance record.
- **Student:** capture Ana Reyes' profile, placement, skills, requirements, attendance, progress, and notifications.

For the cleanest documentation screenshots, use Ana Reyes (`ana.reyes@nemsu.edu.ph`) because her profile includes the most linked workflow records.

## Cleanup

Temporary data can be distinguished from normal records by the documented temporary account names and NEMSU-style test emails. Before deleting or changing it, back up the database. Temporary users can then be removed through the database or with a controlled local cleanup script; do not run automatic cleanup against production.
