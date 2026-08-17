CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE TYPE question_type AS ENUM (
    'text',
    'textarea',
    'single_choice',
    'multiple_choice',
    'boolean',
    'number',
    'date'
);

CREATE TYPE questionnaire_status AS ENUM (
    'draft',
    'submitted',
    'archived'
);

CREATE TYPE result_status AS ENUM (
    'applicable',
    'manual_review',
    'not_applicable'
);

CREATE TYPE relation_kind AS ENUM (
    'include',
    'manual_review',
    'exclude'
);

CREATE TABLE admin_users (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    name text NOT NULL,
    email text NOT NULL UNIQUE,
    password_hash text NOT NULL,
    role text NOT NULL DEFAULT 'admin',
    is_active boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE import_batches (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    source_file text NOT NULL,
    source_hash text,
    imported_by uuid REFERENCES admin_users(id),
    notes text,
    created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE question_sections (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    code text NOT NULL UNIQUE,
    title text NOT NULL,
    description text,
    display_order integer NOT NULL DEFAULT 0,
    is_active boolean NOT NULL DEFAULT true,
    source_workbook text,
    source_sheet text,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE questions (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    section_id uuid NOT NULL REFERENCES question_sections(id) ON DELETE RESTRICT,
    code text NOT NULL UNIQUE,
    text text NOT NULL,
    help_text text,
    type question_type NOT NULL,
    is_required boolean NOT NULL DEFAULT false,
    display_order integer NOT NULL DEFAULT 0,
    is_active boolean NOT NULL DEFAULT true,
    source_workbook text,
    source_sheet text,
    source_row integer,
    metadata jsonb NOT NULL DEFAULT '{}'::jsonb,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX idx_questions_section_order ON questions(section_id, display_order);

CREATE TABLE question_options (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    question_id uuid NOT NULL REFERENCES questions(id) ON DELETE CASCADE,
    code text NOT NULL,
    label text NOT NULL,
    value text NOT NULL,
    display_order integer NOT NULL DEFAULT 0,
    is_active boolean NOT NULL DEFAULT true,
    metadata jsonb NOT NULL DEFAULT '{}'::jsonb,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_question_options_code UNIQUE (question_id, code)
);

CREATE INDEX idx_question_options_question_order ON question_options(question_id, display_order);

CREATE TABLE criteria (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    code text NOT NULL UNIQUE,
    label text NOT NULL,
    description text,
    source_workbook text,
    source_sheet text,
    source_row integer,
    source_column integer,
    is_active boolean NOT NULL DEFAULT true,
    metadata jsonb NOT NULL DEFAULT '{}'::jsonb,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE question_option_criteria (
    question_option_id uuid NOT NULL REFERENCES question_options(id) ON DELETE CASCADE,
    criterion_id uuid NOT NULL REFERENCES criteria(id) ON DELETE CASCADE,
    effect boolean NOT NULL DEFAULT true,
    weight numeric(10,4) NOT NULL DEFAULT 1,
    notes text,
    created_at timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (question_option_id, criterion_id)
);

CREATE TABLE answer_criteria_rules (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    question_id uuid NOT NULL REFERENCES questions(id) ON DELETE CASCADE,
    criterion_id uuid NOT NULL REFERENCES criteria(id) ON DELETE CASCADE,
    operator text NOT NULL,
    compare_value jsonb,
    effect boolean NOT NULL DEFAULT true,
    priority integer NOT NULL DEFAULT 0,
    is_active boolean NOT NULL DEFAULT true,
    notes text,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX idx_answer_criteria_rules_question ON answer_criteria_rules(question_id, is_active);

CREATE TABLE questionnaires (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    questionnaire_code text NOT NULL UNIQUE,
    project_name text,
    project_aru_code text,
    service_name text,
    customer_name text,
    submitted_by_name text,
    submitted_by_email text,
    status questionnaire_status NOT NULL DEFAULT 'draft',
    metadata jsonb NOT NULL DEFAULT '{}'::jsonb,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    submitted_at timestamptz
);

CREATE INDEX idx_questionnaires_status_created ON questionnaires(status, created_at);

CREATE TABLE questionnaire_answers (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    questionnaire_id uuid NOT NULL REFERENCES questionnaires(id) ON DELETE CASCADE,
    question_id uuid NOT NULL REFERENCES questions(id) ON DELETE RESTRICT,
    answer_value jsonb NOT NULL DEFAULT '{}'::jsonb,
    note text,
    answered_at timestamptz NOT NULL DEFAULT now(),
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_questionnaire_answers_question UNIQUE (questionnaire_id, question_id)
);

CREATE INDEX idx_questionnaire_answers_questionnaire ON questionnaire_answers(questionnaire_id);

CREATE TABLE questionnaire_answer_options (
    answer_id uuid NOT NULL REFERENCES questionnaire_answers(id) ON DELETE CASCADE,
    question_option_id uuid NOT NULL REFERENCES question_options(id) ON DELETE RESTRICT,
    created_at timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (answer_id, question_option_id)
);

CREATE TABLE questionnaire_criteria (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    questionnaire_id uuid NOT NULL REFERENCES questionnaires(id) ON DELETE CASCADE,
    criterion_id uuid NOT NULL REFERENCES criteria(id) ON DELETE RESTRICT,
    source_answer_id uuid REFERENCES questionnaire_answers(id) ON DELETE SET NULL,
    value boolean NOT NULL DEFAULT true,
    reason text,
    created_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_questionnaire_criteria UNIQUE (questionnaire_id, criterion_id)
);

CREATE INDEX idx_questionnaire_criteria_questionnaire ON questionnaire_criteria(questionnaire_id);
CREATE INDEX idx_questionnaire_criteria_criterion ON questionnaire_criteria(criterion_id);

CREATE TABLE requirements (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    requirement_code text NOT NULL UNIQUE,
    category text,
    subcategory text,
    version text,
    title text NOT NULL,
    description text,
    applicability_context text,
    notes text,
    importance text,
    standard text,
    owner text,
    is_active boolean NOT NULL DEFAULT true,
    source_workbook text,
    source_sheet text,
    source_row integer,
    import_batch_id uuid REFERENCES import_batches(id),
    metadata jsonb NOT NULL DEFAULT '{}'::jsonb,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX idx_requirements_category ON requirements(category, subcategory);
CREATE INDEX idx_requirements_importance ON requirements(importance);

CREATE TABLE requirement_organizational_units (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    requirement_id uuid NOT NULL REFERENCES requirements(id) ON DELETE CASCADE,
    unit_name text NOT NULL,
    is_applicable boolean,
    is_standardized boolean,
    link text,
    notes text,
    created_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_requirement_unit UNIQUE (requirement_id, unit_name)
);

CREATE TABLE requirement_criteria (
    requirement_id uuid NOT NULL REFERENCES requirements(id) ON DELETE CASCADE,
    criterion_id uuid NOT NULL REFERENCES criteria(id) ON DELETE CASCADE,
    relation relation_kind NOT NULL DEFAULT 'include',
    weight numeric(10,4) NOT NULL DEFAULT 1,
    notes text,
    created_at timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (requirement_id, criterion_id, relation)
);

CREATE INDEX idx_requirement_criteria_criterion ON requirement_criteria(criterion_id);

CREATE TABLE services (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    service_code text NOT NULL UNIQUE,
    owner_department text,
    billing_type text,
    portfolio_category text,
    macro_service text,
    category text,
    elementary_service text NOT NULL,
    description text,
    ordinary_activity_type text,
    output_measurability text,
    commessa text,
    check_component text,
    primary_asset text,
    software text,
    service_hours text,
    notes text,
    is_active boolean NOT NULL DEFAULT true,
    source_workbook text,
    source_sheet text,
    source_row integer,
    import_batch_id uuid REFERENCES import_batches(id),
    metadata jsonb NOT NULL DEFAULT '{}'::jsonb,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX idx_services_owner ON services(owner_department);
CREATE INDEX idx_services_category ON services(category, macro_service);

CREATE TABLE service_criteria (
    service_id uuid NOT NULL REFERENCES services(id) ON DELETE CASCADE,
    criterion_id uuid NOT NULL REFERENCES criteria(id) ON DELETE CASCADE,
    relation relation_kind NOT NULL DEFAULT 'include',
    weight numeric(10,4) NOT NULL DEFAULT 1,
    notes text,
    created_at timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (service_id, criterion_id, relation)
);

CREATE INDEX idx_service_criteria_criterion ON service_criteria(criterion_id);

CREATE TABLE service_requirements (
    service_id uuid NOT NULL REFERENCES services(id) ON DELETE CASCADE,
    requirement_id uuid NOT NULL REFERENCES requirements(id) ON DELETE CASCADE,
    relation relation_kind NOT NULL DEFAULT 'include',
    weight numeric(10,4) NOT NULL DEFAULT 1,
    notes text,
    created_at timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (service_id, requirement_id, relation)
);

CREATE INDEX idx_service_requirements_requirement ON service_requirements(requirement_id);

CREATE TABLE documents (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    document_code text NOT NULL UNIQUE,
    title text NOT NULL,
    document_type text,
    description text,
    storage_path text,
    external_url text,
    checksum text,
    is_active boolean NOT NULL DEFAULT true,
    metadata jsonb NOT NULL DEFAULT '{}'::jsonb,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT chk_document_location CHECK (storage_path IS NOT NULL OR external_url IS NOT NULL)
);

CREATE TABLE requirement_documents (
    requirement_id uuid NOT NULL REFERENCES requirements(id) ON DELETE CASCADE,
    document_id uuid NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
    notes text,
    created_at timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (requirement_id, document_id)
);

CREATE TABLE service_documents (
    service_id uuid NOT NULL REFERENCES services(id) ON DELETE CASCADE,
    document_id uuid NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
    notes text,
    created_at timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (service_id, document_id)
);

CREATE TABLE criterion_documents (
    criterion_id uuid NOT NULL REFERENCES criteria(id) ON DELETE CASCADE,
    document_id uuid NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
    notes text,
    created_at timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (criterion_id, document_id)
);

CREATE TABLE questionnaire_requirement_results (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    questionnaire_id uuid NOT NULL REFERENCES questionnaires(id) ON DELETE CASCADE,
    requirement_id uuid NOT NULL REFERENCES requirements(id) ON DELETE RESTRICT,
    status result_status NOT NULL,
    score numeric(10,4) NOT NULL DEFAULT 0,
    matched_criteria jsonb NOT NULL DEFAULT '[]'::jsonb,
    result_snapshot jsonb NOT NULL DEFAULT '{}'::jsonb,
    created_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_questionnaire_requirement_result UNIQUE (questionnaire_id, requirement_id)
);

CREATE INDEX idx_questionnaire_requirement_results_questionnaire ON questionnaire_requirement_results(questionnaire_id, status);

CREATE TABLE questionnaire_service_results (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    questionnaire_id uuid NOT NULL REFERENCES questionnaires(id) ON DELETE CASCADE,
    service_id uuid NOT NULL REFERENCES services(id) ON DELETE RESTRICT,
    status result_status NOT NULL,
    score numeric(10,4) NOT NULL DEFAULT 0,
    matched_criteria jsonb NOT NULL DEFAULT '[]'::jsonb,
    matched_requirements jsonb NOT NULL DEFAULT '[]'::jsonb,
    result_snapshot jsonb NOT NULL DEFAULT '{}'::jsonb,
    created_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_questionnaire_service_result UNIQUE (questionnaire_id, service_id)
);

CREATE INDEX idx_questionnaire_service_results_questionnaire ON questionnaire_service_results(questionnaire_id, status);

CREATE TABLE audit_log (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    actor_user_id uuid REFERENCES admin_users(id),
    entity_name text NOT NULL,
    entity_id uuid,
    action text NOT NULL,
    old_values jsonb,
    new_values jsonb,
    created_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX idx_audit_log_entity ON audit_log(entity_name, entity_id);
CREATE INDEX idx_audit_log_created ON audit_log(created_at);

