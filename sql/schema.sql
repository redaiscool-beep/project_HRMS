--
-- PostgreSQL database dump
--

\restrict r39akYDndSFEYkcq1CRc81Wba8tvTz8388anbKcqPVl0j0fYGjldfgkAhl3o2WS

-- Dumped from database version 16.15 (Homebrew)
-- Dumped by pg_dump version 16.15 (Homebrew)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: hr_department; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hr_department (
    department_id integer NOT NULL,
    department_code character varying(20) NOT NULL,
    department_name_th character varying(150) NOT NULL,
    department_name_en character varying(150),
    parent_department_id integer,
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    remark text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: hr_department_department_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hr_department_department_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hr_department_department_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hr_department_department_id_seq OWNED BY public.hr_department.department_id;


--
-- Name: hr_employee; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hr_employee (
    employee_id integer NOT NULL,
    employee_code character varying(20) NOT NULL,
    prefix character varying(20),
    first_name character varying(100) NOT NULL,
    last_name character varying(100) NOT NULL,
    first_name_en character varying(100),
    last_name_en character varying(100),
    gender character varying(10),
    birth_date date,
    national_id character varying(20),
    email character varying(150),
    phone character varying(20),
    address text,
    department_id integer NOT NULL,
    position_id integer NOT NULL,
    employee_type_id integer NOT NULL,
    hire_date date NOT NULL,
    probation_end_date date,
    resign_date date,
    employment_status character varying(20) NOT NULL,
    profile_image character varying(255),
    remark text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT hr_employee_employment_status_check CHECK (((employment_status)::text = ANY ((ARRAY['ACTIVE'::character varying, 'PROBATION'::character varying, 'RESIGNED'::character varying, 'INACTIVE'::character varying])::text[])))
);


--
-- Name: hr_employee_certificate; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hr_employee_certificate (
    certificate_id integer NOT NULL,
    employee_id integer NOT NULL,
    certificate_name character varying(200) NOT NULL,
    certificate_no character varying(100),
    issued_by character varying(150),
    issue_date date,
    expire_date date,
    certificate_file character varying(255),
    remark text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: hr_employee_certificate_certificate_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hr_employee_certificate_certificate_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hr_employee_certificate_certificate_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hr_employee_certificate_certificate_id_seq OWNED BY public.hr_employee_certificate.certificate_id;


--
-- Name: hr_employee_education; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hr_employee_education (
    education_id integer NOT NULL,
    employee_id integer NOT NULL,
    education_level character varying(50),
    degree_name character varying(150),
    major_name character varying(150),
    faculty_name character varying(150),
    institute_name character varying(150),
    start_year integer,
    graduation_year integer,
    gpa numeric(3,2),
    remark text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: hr_employee_education_education_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hr_employee_education_education_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hr_employee_education_education_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hr_employee_education_education_id_seq OWNED BY public.hr_employee_education.education_id;


--
-- Name: hr_employee_employee_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hr_employee_employee_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hr_employee_employee_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hr_employee_employee_id_seq OWNED BY public.hr_employee.employee_id;


--
-- Name: hr_employee_movement; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hr_employee_movement (
    movement_id integer NOT NULL,
    employee_id integer NOT NULL,
    movement_type character varying(30) NOT NULL,
    effective_date date NOT NULL,
    old_department_id integer,
    new_department_id integer,
    old_position_id integer,
    new_position_id integer,
    old_employee_type_id integer,
    new_employee_type_id integer,
    old_status character varying(20),
    new_status character varying(20),
    remark text,
    created_by bigint,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT hr_employee_movement_movement_type_check CHECK (((movement_type)::text = ANY ((ARRAY['HIRE'::character varying, 'PASS_PROBATION'::character varying, 'TRANSFER'::character varying, 'POSITION_CHANGE'::character varying, 'PROMOTION'::character varying, 'STATUS_CHANGE'::character varying, 'RESIGN'::character varying])::text[])))
);


--
-- Name: hr_employee_movement_movement_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hr_employee_movement_movement_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hr_employee_movement_movement_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hr_employee_movement_movement_id_seq OWNED BY public.hr_employee_movement.movement_id;


--
-- Name: hr_employee_skill; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hr_employee_skill (
    skill_id integer NOT NULL,
    employee_id integer NOT NULL,
    skill_name character varying(150) NOT NULL,
    skill_level character varying(20),
    experience_year integer,
    remark text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT hr_employee_skill_skill_level_check CHECK (((skill_level)::text = ANY ((ARRAY['BEGINNER'::character varying, 'INTERMEDIATE'::character varying, 'ADVANCED'::character varying, 'EXPERT'::character varying])::text[])))
);


--
-- Name: hr_employee_skill_skill_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hr_employee_skill_skill_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hr_employee_skill_skill_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hr_employee_skill_skill_id_seq OWNED BY public.hr_employee_skill.skill_id;


--
-- Name: hr_employee_training; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hr_employee_training (
    training_id integer NOT NULL,
    employee_id integer NOT NULL,
    course_name character varying(200) NOT NULL,
    training_provider character varying(150),
    training_type character varying(100),
    start_date date,
    end_date date,
    duration numeric(6,2),
    duration_unit character varying(10),
    certificate_received boolean DEFAULT false NOT NULL,
    remark text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT hr_employee_training_duration_unit_check CHECK (((duration_unit)::text = ANY ((ARRAY['HOUR'::character varying, 'DAY'::character varying, 'MONTH'::character varying])::text[])))
);


--
-- Name: hr_employee_training_training_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hr_employee_training_training_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hr_employee_training_training_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hr_employee_training_training_id_seq OWNED BY public.hr_employee_training.training_id;


--
-- Name: hr_employee_type; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hr_employee_type (
    employee_type_id integer NOT NULL,
    employee_type_code character varying(20) NOT NULL,
    employee_type_name character varying(100) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    remark character varying(100),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: hr_employee_type_employee_type_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hr_employee_type_employee_type_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hr_employee_type_employee_type_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hr_employee_type_employee_type_id_seq OWNED BY public.hr_employee_type.employee_type_id;


--
-- Name: hr_employee_work_history; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hr_employee_work_history (
    work_history_id integer NOT NULL,
    employee_id integer NOT NULL,
    company_name character varying(150),
    position_name character varying(150),
    start_date date,
    end_date date,
    salary numeric(12,2),
    job_description text,
    reason_for_leaving text,
    remark text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: hr_employee_work_history_work_history_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hr_employee_work_history_work_history_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hr_employee_work_history_work_history_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hr_employee_work_history_work_history_id_seq OWNED BY public.hr_employee_work_history.work_history_id;


--
-- Name: hr_leave_request; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hr_leave_request (
    leave_request_id integer NOT NULL,
    employee_id integer NOT NULL,
    leave_type_id integer NOT NULL,
    start_date date NOT NULL,
    end_date date NOT NULL,
    total_days numeric(5,2) NOT NULL,
    reason text,
    document_status character varying(20) DEFAULT 'DRAFT'::character varying NOT NULL,
    approved_by bigint,
    approved_at timestamp without time zone,
    remark text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_by bigint,
    CONSTRAINT hr_leave_request_document_status_check CHECK (((document_status)::text = ANY ((ARRAY['DRAFT'::character varying, 'SUBMITTED'::character varying, 'APPROVED'::character varying, 'REJECTED'::character varying, 'CANCELLED'::character varying])::text[])))
);


--
-- Name: hr_leave_request_leave_request_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hr_leave_request_leave_request_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hr_leave_request_leave_request_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hr_leave_request_leave_request_id_seq OWNED BY public.hr_leave_request.leave_request_id;


--
-- Name: hr_leave_type; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hr_leave_type (
    leave_type_id integer NOT NULL,
    leave_type_code character varying(20) NOT NULL,
    leave_type_name character varying(100) NOT NULL,
    max_days integer,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: hr_leave_type_leave_type_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hr_leave_type_leave_type_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hr_leave_type_leave_type_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hr_leave_type_leave_type_id_seq OWNED BY public.hr_leave_type.leave_type_id;


--
-- Name: hr_position; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hr_position (
    position_id integer NOT NULL,
    position_code character varying(20) NOT NULL,
    position_name_th character varying(150) NOT NULL,
    position_name_en character varying(150),
    position_level integer,
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    remark character varying(150),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: hr_position_position_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hr_position_position_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hr_position_position_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hr_position_position_id_seq OWNED BY public.hr_position.position_id;


--
-- Name: train_users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.train_users (
    id bigint NOT NULL,
    username character varying(100) NOT NULL,
    password_hash character varying(255) NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    role character varying(20),
    is_active boolean DEFAULT true NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: train_users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.train_users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: train_users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.train_users_id_seq OWNED BY public.train_users.id;


--
-- Name: hr_department department_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_department ALTER COLUMN department_id SET DEFAULT nextval('public.hr_department_department_id_seq'::regclass);


--
-- Name: hr_employee employee_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee ALTER COLUMN employee_id SET DEFAULT nextval('public.hr_employee_employee_id_seq'::regclass);


--
-- Name: hr_employee_certificate certificate_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_certificate ALTER COLUMN certificate_id SET DEFAULT nextval('public.hr_employee_certificate_certificate_id_seq'::regclass);


--
-- Name: hr_employee_education education_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_education ALTER COLUMN education_id SET DEFAULT nextval('public.hr_employee_education_education_id_seq'::regclass);


--
-- Name: hr_employee_movement movement_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_movement ALTER COLUMN movement_id SET DEFAULT nextval('public.hr_employee_movement_movement_id_seq'::regclass);


--
-- Name: hr_employee_skill skill_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_skill ALTER COLUMN skill_id SET DEFAULT nextval('public.hr_employee_skill_skill_id_seq'::regclass);


--
-- Name: hr_employee_training training_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_training ALTER COLUMN training_id SET DEFAULT nextval('public.hr_employee_training_training_id_seq'::regclass);


--
-- Name: hr_employee_type employee_type_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_type ALTER COLUMN employee_type_id SET DEFAULT nextval('public.hr_employee_type_employee_type_id_seq'::regclass);


--
-- Name: hr_employee_work_history work_history_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_work_history ALTER COLUMN work_history_id SET DEFAULT nextval('public.hr_employee_work_history_work_history_id_seq'::regclass);


--
-- Name: hr_leave_request leave_request_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_leave_request ALTER COLUMN leave_request_id SET DEFAULT nextval('public.hr_leave_request_leave_request_id_seq'::regclass);


--
-- Name: hr_leave_type leave_type_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_leave_type ALTER COLUMN leave_type_id SET DEFAULT nextval('public.hr_leave_type_leave_type_id_seq'::regclass);


--
-- Name: hr_position position_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_position ALTER COLUMN position_id SET DEFAULT nextval('public.hr_position_position_id_seq'::regclass);


--
-- Name: train_users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.train_users ALTER COLUMN id SET DEFAULT nextval('public.train_users_id_seq'::regclass);


--
-- Name: hr_department hr_department_department_code_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_department
    ADD CONSTRAINT hr_department_department_code_key UNIQUE (department_code);


--
-- Name: hr_department hr_department_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_department
    ADD CONSTRAINT hr_department_pkey PRIMARY KEY (department_id);


--
-- Name: hr_employee_certificate hr_employee_certificate_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_certificate
    ADD CONSTRAINT hr_employee_certificate_pkey PRIMARY KEY (certificate_id);


--
-- Name: hr_employee_education hr_employee_education_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_education
    ADD CONSTRAINT hr_employee_education_pkey PRIMARY KEY (education_id);


--
-- Name: hr_employee hr_employee_employee_code_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee
    ADD CONSTRAINT hr_employee_employee_code_key UNIQUE (employee_code);


--
-- Name: hr_employee_movement hr_employee_movement_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_movement
    ADD CONSTRAINT hr_employee_movement_pkey PRIMARY KEY (movement_id);


--
-- Name: hr_employee hr_employee_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee
    ADD CONSTRAINT hr_employee_pkey PRIMARY KEY (employee_id);


--
-- Name: hr_employee_skill hr_employee_skill_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_skill
    ADD CONSTRAINT hr_employee_skill_pkey PRIMARY KEY (skill_id);


--
-- Name: hr_employee_training hr_employee_training_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_training
    ADD CONSTRAINT hr_employee_training_pkey PRIMARY KEY (training_id);


--
-- Name: hr_employee_type hr_employee_type_employee_type_code_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_type
    ADD CONSTRAINT hr_employee_type_employee_type_code_key UNIQUE (employee_type_code);


--
-- Name: hr_employee_type hr_employee_type_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_type
    ADD CONSTRAINT hr_employee_type_pkey PRIMARY KEY (employee_type_id);


--
-- Name: hr_employee_work_history hr_employee_work_history_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_work_history
    ADD CONSTRAINT hr_employee_work_history_pkey PRIMARY KEY (work_history_id);


--
-- Name: hr_leave_request hr_leave_request_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_leave_request
    ADD CONSTRAINT hr_leave_request_pkey PRIMARY KEY (leave_request_id);


--
-- Name: hr_leave_type hr_leave_type_leave_type_code_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_leave_type
    ADD CONSTRAINT hr_leave_type_leave_type_code_key UNIQUE (leave_type_code);


--
-- Name: hr_leave_type hr_leave_type_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_leave_type
    ADD CONSTRAINT hr_leave_type_pkey PRIMARY KEY (leave_type_id);


--
-- Name: hr_position hr_position_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_position
    ADD CONSTRAINT hr_position_pkey PRIMARY KEY (position_id);


--
-- Name: hr_position hr_position_position_code_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_position
    ADD CONSTRAINT hr_position_position_code_key UNIQUE (position_code);


--
-- Name: train_users train_users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.train_users
    ADD CONSTRAINT train_users_pkey PRIMARY KEY (id);


--
-- Name: train_users train_users_username_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.train_users
    ADD CONSTRAINT train_users_username_key UNIQUE (username);


--
-- Name: hr_department hr_department_parent_department_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_department
    ADD CONSTRAINT hr_department_parent_department_id_fkey FOREIGN KEY (parent_department_id) REFERENCES public.hr_department(department_id);


--
-- Name: hr_employee_certificate hr_employee_certificate_employee_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_certificate
    ADD CONSTRAINT hr_employee_certificate_employee_id_fkey FOREIGN KEY (employee_id) REFERENCES public.hr_employee(employee_id);


--
-- Name: hr_employee hr_employee_department_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee
    ADD CONSTRAINT hr_employee_department_id_fkey FOREIGN KEY (department_id) REFERENCES public.hr_department(department_id);


--
-- Name: hr_employee_education hr_employee_education_employee_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_education
    ADD CONSTRAINT hr_employee_education_employee_id_fkey FOREIGN KEY (employee_id) REFERENCES public.hr_employee(employee_id);


--
-- Name: hr_employee hr_employee_employee_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee
    ADD CONSTRAINT hr_employee_employee_type_id_fkey FOREIGN KEY (employee_type_id) REFERENCES public.hr_employee_type(employee_type_id);


--
-- Name: hr_employee_movement hr_employee_movement_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_movement
    ADD CONSTRAINT hr_employee_movement_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.train_users(id);


--
-- Name: hr_employee_movement hr_employee_movement_employee_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_movement
    ADD CONSTRAINT hr_employee_movement_employee_id_fkey FOREIGN KEY (employee_id) REFERENCES public.hr_employee(employee_id);


--
-- Name: hr_employee_movement hr_employee_movement_new_department_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_movement
    ADD CONSTRAINT hr_employee_movement_new_department_id_fkey FOREIGN KEY (new_department_id) REFERENCES public.hr_department(department_id);


--
-- Name: hr_employee_movement hr_employee_movement_new_employee_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_movement
    ADD CONSTRAINT hr_employee_movement_new_employee_type_id_fkey FOREIGN KEY (new_employee_type_id) REFERENCES public.hr_employee_type(employee_type_id);


--
-- Name: hr_employee_movement hr_employee_movement_new_position_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_movement
    ADD CONSTRAINT hr_employee_movement_new_position_id_fkey FOREIGN KEY (new_position_id) REFERENCES public.hr_position(position_id);


--
-- Name: hr_employee_movement hr_employee_movement_old_department_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_movement
    ADD CONSTRAINT hr_employee_movement_old_department_id_fkey FOREIGN KEY (old_department_id) REFERENCES public.hr_department(department_id);


--
-- Name: hr_employee_movement hr_employee_movement_old_employee_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_movement
    ADD CONSTRAINT hr_employee_movement_old_employee_type_id_fkey FOREIGN KEY (old_employee_type_id) REFERENCES public.hr_employee_type(employee_type_id);


--
-- Name: hr_employee_movement hr_employee_movement_old_position_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_movement
    ADD CONSTRAINT hr_employee_movement_old_position_id_fkey FOREIGN KEY (old_position_id) REFERENCES public.hr_position(position_id);


--
-- Name: hr_employee hr_employee_position_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee
    ADD CONSTRAINT hr_employee_position_id_fkey FOREIGN KEY (position_id) REFERENCES public.hr_position(position_id);


--
-- Name: hr_employee_skill hr_employee_skill_employee_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_skill
    ADD CONSTRAINT hr_employee_skill_employee_id_fkey FOREIGN KEY (employee_id) REFERENCES public.hr_employee(employee_id);


--
-- Name: hr_employee_training hr_employee_training_employee_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_training
    ADD CONSTRAINT hr_employee_training_employee_id_fkey FOREIGN KEY (employee_id) REFERENCES public.hr_employee(employee_id);


--
-- Name: hr_employee_work_history hr_employee_work_history_employee_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_employee_work_history
    ADD CONSTRAINT hr_employee_work_history_employee_id_fkey FOREIGN KEY (employee_id) REFERENCES public.hr_employee(employee_id);


--
-- Name: hr_leave_request hr_leave_request_approved_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_leave_request
    ADD CONSTRAINT hr_leave_request_approved_by_fkey FOREIGN KEY (approved_by) REFERENCES public.train_users(id);


--
-- Name: hr_leave_request hr_leave_request_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_leave_request
    ADD CONSTRAINT hr_leave_request_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.train_users(id);


--
-- Name: hr_leave_request hr_leave_request_employee_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_leave_request
    ADD CONSTRAINT hr_leave_request_employee_id_fkey FOREIGN KEY (employee_id) REFERENCES public.hr_employee(employee_id);


--
-- Name: hr_leave_request hr_leave_request_leave_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hr_leave_request
    ADD CONSTRAINT hr_leave_request_leave_type_id_fkey FOREIGN KEY (leave_type_id) REFERENCES public.hr_leave_type(leave_type_id);


--
-- PostgreSQL database dump complete
--

\unrestrict r39akYDndSFEYkcq1CRc81Wba8tvTz8388anbKcqPVl0j0fYGjldfgkAhl3o2WS

