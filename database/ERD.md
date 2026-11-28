    # Entity Relationship Diagram

The following Mermaid diagram documents the Phase 2 relational schema.

```mermaid
erDiagram
    users ||--o| student_profiles : owns
    programs ||--o{ student_profiles : categorizes
    companies ||--o{ company_supervisors : employs
    users ||--o{ company_supervisors : supervisor_account
    companies ||--o{ company_positions : offers
    skills ||--o{ student_skills : catalog
    student_profiles ||--o{ student_skills : has
    skills ||--o{ position_skills : required_by
    company_positions ||--o{ position_skills : requires
    student_profiles ||--o{ student_preferences : prefers
    companies o|--o{ student_preferences : option
    company_positions o|--o{ student_preferences : option
    student_profiles ||--o{ placements : receives
    company_positions ||--o{ placements : contains
    users ||--o{ placements : assigns
    users o|--o{ placements : approves
    student_profiles ||--o{ requirement_submissions : submits
    requirement_types ||--o{ requirement_submissions : defines
    users o|--o{ requirement_submissions : reviews
    placements ||--o{ attendance_records : has
    users o|--o{ attendance_records : verifies
    placements ||--o{ evaluations : receives
    users ||--o{ evaluations : supervisor           
    users ||--o{ notifications : receives

    users {
        bigint id PK
        varchar name
        varchar email UK
        varchar password
        enum role
        enum status
    }
    programs {
        bigint id PK
        varchar code UK
        varchar name
    }
    student_profiles {
        bigint id PK
        bigint user_id UK,FK
        bigint program_id FK
        varchar student_number UK
        tinyint year_level
    }
    placements {
        bigint id PK
        bigint student_profile_id FK
        bigint position_id FK
        bigint assigned_by FK
        bigint approved_by FK
        smallint required_hours
        enum status
    }
    attendance_records {
        bigint id PK
        bigint placement_id FK
        date attendance_date
        decimal total_hours
        enum status
    }
    evaluations {
        bigint id PK
        bigint placement_id FK
        bigint supervisor_id FK
        tinyint rating
    }

