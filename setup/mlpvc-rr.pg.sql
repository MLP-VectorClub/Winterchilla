--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: deviation_cache; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE deviation_cache (
    provider character(6) NOT NULL,
    id character varying(20) NOT NULL,
    title character varying(255) NOT NULL,
    author character varying(20),
    preview character varying(255) NOT NULL,
    fullsize character varying(255) NOT NULL,
    updated_on timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE deviation_cache OWNER TO "mlpvc-rr";

--
-- Name: episodes; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE episodes (
    season integer NOT NULL,
    episode integer NOT NULL,
    twoparter boolean DEFAULT false NOT NULL,
    title character varying(35) NOT NULL,
    posted timestamp with time zone DEFAULT now() NOT NULL,
    posted_by uuid,
    airs timestamp with time zone
);


ALTER TABLE episodes OWNER TO "mlpvc-rr";

--
-- Name: episodes__videos; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE episodes__videos (
    season integer NOT NULL,
    episode integer NOT NULL,
    provider character(2) NOT NULL,
    id character varying(15) NOT NULL
);


ALTER TABLE episodes__videos OWNER TO "mlpvc-rr";

--
-- Name: episodes__votes; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE episodes__votes (
    season integer NOT NULL,
    episode integer NOT NULL,
    "user" uuid NOT NULL,
    vote smallint NOT NULL
);


ALTER TABLE episodes__votes OWNER TO "mlpvc-rr";

--
-- Name: log; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE log (
    entryid integer NOT NULL,
    initiator uuid,
    reftype character varying(20) NOT NULL,
    refid integer,
    "timestamp" timestamp with time zone DEFAULT now() NOT NULL,
    ip character varying(255)
);


ALTER TABLE log OWNER TO "mlpvc-rr";

--
-- Name: log__banish; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE log__banish (
    entryid integer NOT NULL,
    target uuid NOT NULL,
    reason character varying(255) NOT NULL
);


ALTER TABLE log__banish OWNER TO "mlpvc-rr";

--
-- Name: log__banish_entryid_seq; Type: SEQUENCE; Schema: public; Owner: mlpvc-rr
--

CREATE SEQUENCE log__banish_entryid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE log__banish_entryid_seq OWNER TO "mlpvc-rr";

--
-- Name: log__banish_entryid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: mlpvc-rr
--

ALTER SEQUENCE log__banish_entryid_seq OWNED BY log__banish.entryid;


--
-- Name: log__color_modify; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE log__color_modify (
    entryid integer NOT NULL,
    ponyid integer,
    reason character varying(255) NOT NULL
);


ALTER TABLE log__color_modify OWNER TO "mlpvc-rr";

--
-- Name: log__color_modify_entryid_seq; Type: SEQUENCE; Schema: public; Owner: mlpvc-rr
--

CREATE SEQUENCE log__color_modify_entryid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE log__color_modify_entryid_seq OWNER TO "mlpvc-rr";

--
-- Name: log__color_modify_entryid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: mlpvc-rr
--

ALTER SEQUENCE log__color_modify_entryid_seq OWNED BY log__color_modify.entryid;


--
-- Name: log__episode_modify; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE log__episode_modify (
    entryid integer NOT NULL,
    target character varying(6) NOT NULL,
    oldseason integer,
    newseason integer,
    oldepisode integer,
    newepisode integer,
    oldtwoparter boolean,
    newtwoparter boolean,
    oldtitle character varying(35),
    newtitle character varying(35),
    oldairs timestamp without time zone,
    newairs timestamp without time zone
);


ALTER TABLE log__episode_modify OWNER TO "mlpvc-rr";

--
-- Name: log__episodes; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE log__episodes (
    entryid integer NOT NULL,
    action character(3) NOT NULL,
    season integer NOT NULL,
    episode integer NOT NULL,
    twoparter boolean NOT NULL,
    title character varying(35) NOT NULL,
    airs timestamp without time zone
);


ALTER TABLE log__episodes OWNER TO "mlpvc-rr";

--
-- Name: log__episodes_entryid_seq; Type: SEQUENCE; Schema: public; Owner: mlpvc-rr
--

CREATE SEQUENCE log__episodes_entryid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE log__episodes_entryid_seq OWNER TO "mlpvc-rr";

--
-- Name: log__episodes_entryid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: mlpvc-rr
--

ALTER SEQUENCE log__episodes_entryid_seq OWNED BY log__episodes.entryid;


--
-- Name: log__img_update; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE log__img_update (
    entryid integer NOT NULL,
    id integer NOT NULL,
    thing character varying(11) NOT NULL,
    oldpreview character varying(255),
    newpreview character varying(255),
    oldfullsize character varying(255),
    newfullsize character varying(255)
);


ALTER TABLE log__img_update OWNER TO "mlpvc-rr";

--
-- Name: log__img_update_entryid_seq; Type: SEQUENCE; Schema: public; Owner: mlpvc-rr
--

CREATE SEQUENCE log__img_update_entryid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE log__img_update_entryid_seq OWNER TO "mlpvc-rr";

--
-- Name: log__img_update_entryid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: mlpvc-rr
--

ALTER SEQUENCE log__img_update_entryid_seq OWNED BY log__img_update.entryid;


--
-- Name: log__post_lock; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE log__post_lock (
    entryid integer NOT NULL,
    type character varying(11) NOT NULL,
    id integer NOT NULL
);


ALTER TABLE log__post_lock OWNER TO "mlpvc-rr";

--
-- Name: log__post_lock_entryid_seq; Type: SEQUENCE; Schema: public; Owner: mlpvc-rr
--

CREATE SEQUENCE log__post_lock_entryid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE log__post_lock_entryid_seq OWNER TO "mlpvc-rr";

--
-- Name: log__post_lock_entryid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: mlpvc-rr
--

ALTER SEQUENCE log__post_lock_entryid_seq OWNED BY log__post_lock.entryid;


--
-- Name: log__req_delete; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE log__req_delete (
    entryid integer NOT NULL,
    id integer,
    season integer,
    episode integer,
    label character varying(255),
    type character varying(4),
    requested_by uuid,
    posted timestamp without time zone,
    reserved_by uuid,
    deviation_id character varying(7),
    lock boolean
);


ALTER TABLE log__req_delete OWNER TO "mlpvc-rr";

--
-- Name: log__req_delete_entryid_seq; Type: SEQUENCE; Schema: public; Owner: mlpvc-rr
--

CREATE SEQUENCE log__req_delete_entryid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE log__req_delete_entryid_seq OWNER TO "mlpvc-rr";

--
-- Name: log__req_delete_entryid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: mlpvc-rr
--

ALTER SEQUENCE log__req_delete_entryid_seq OWNED BY log__req_delete.entryid;


--
-- Name: log__rolechange; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE log__rolechange (
    entryid integer NOT NULL,
    target uuid NOT NULL,
    oldrole character varying(10) NOT NULL,
    newrole character varying(10) NOT NULL
);


ALTER TABLE log__rolechange OWNER TO "mlpvc-rr";

--
-- Name: log__rolechange_entryid_seq; Type: SEQUENCE; Schema: public; Owner: mlpvc-rr
--

CREATE SEQUENCE log__rolechange_entryid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE log__rolechange_entryid_seq OWNER TO "mlpvc-rr";

--
-- Name: log__rolechange_entryid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: mlpvc-rr
--

ALTER SEQUENCE log__rolechange_entryid_seq OWNED BY log__rolechange.entryid;


--
-- Name: log__un-banish; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE "log__un-banish" (
    entryid integer NOT NULL,
    target uuid NOT NULL,
    reason character varying(255) NOT NULL
);


ALTER TABLE "log__un-banish" OWNER TO "mlpvc-rr";

--
-- Name: log__un-banish_entryid_seq; Type: SEQUENCE; Schema: public; Owner: mlpvc-rr
--

CREATE SEQUENCE "log__un-banish_entryid_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE "log__un-banish_entryid_seq" OWNER TO "mlpvc-rr";

--
-- Name: log__un-banish_entryid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: mlpvc-rr
--

ALTER SEQUENCE "log__un-banish_entryid_seq" OWNED BY "log__un-banish".entryid;


--
-- Name: log__userfetch; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE log__userfetch (
    entryid integer NOT NULL,
    userid uuid NOT NULL
);


ALTER TABLE log__userfetch OWNER TO "mlpvc-rr";

--
-- Name: log__userfetch_entryid_seq; Type: SEQUENCE; Schema: public; Owner: mlpvc-rr
--

CREATE SEQUENCE log__userfetch_entryid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE log__userfetch_entryid_seq OWNER TO "mlpvc-rr";

--
-- Name: log__userfetch_entryid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: mlpvc-rr
--

ALTER SEQUENCE log__userfetch_entryid_seq OWNED BY log__userfetch.entryid;


--
-- Name: log_entryid_seq; Type: SEQUENCE; Schema: public; Owner: mlpvc-rr
--

CREATE SEQUENCE log_entryid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE log_entryid_seq OWNER TO "mlpvc-rr";

--
-- Name: log_entryid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: mlpvc-rr
--

ALTER SEQUENCE log_entryid_seq OWNED BY log.entryid;


--
-- Name: requests; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE requests (
    id integer NOT NULL,
    type character varying(3) NOT NULL,
    season integer NOT NULL,
    episode integer NOT NULL,
    preview character varying(255) NOT NULL,
    fullsize character varying(255) NOT NULL,
    label character varying(255) NOT NULL,
    requested_by uuid,
    posted timestamp with time zone DEFAULT now() NOT NULL,
    reserved_by uuid,
    deviation_id character varying(7),
    lock boolean DEFAULT false NOT NULL,
    reserved_at timestamp with time zone
);


ALTER TABLE requests OWNER TO "mlpvc-rr";

--
-- Name: requests_id_seq; Type: SEQUENCE; Schema: public; Owner: mlpvc-rr
--

CREATE SEQUENCE requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE requests_id_seq OWNER TO "mlpvc-rr";

--
-- Name: requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: mlpvc-rr
--

ALTER SEQUENCE requests_id_seq OWNED BY requests.id;


--
-- Name: reservations; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE reservations (
    id integer NOT NULL,
    season integer NOT NULL,
    episode integer NOT NULL,
    preview character varying(255),
    fullsize character varying(255),
    label character varying(255),
    posted timestamp with time zone DEFAULT now() NOT NULL,
    reserved_by uuid,
    deviation_id character varying(7),
    lock boolean DEFAULT false NOT NULL
);


ALTER TABLE reservations OWNER TO "mlpvc-rr";

--
-- Name: reservations_id_seq; Type: SEQUENCE; Schema: public; Owner: mlpvc-rr
--

CREATE SEQUENCE reservations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE reservations_id_seq OWNER TO "mlpvc-rr";

--
-- Name: reservations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: mlpvc-rr
--

ALTER SEQUENCE reservations_id_seq OWNED BY reservations.id;


--
-- Name: roles; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE roles (
    value integer NOT NULL,
    name character varying(10) NOT NULL,
    label character varying(30) NOT NULL
);


ALTER TABLE roles OWNER TO "mlpvc-rr";

--
-- Name: sessions; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE sessions (
    id integer NOT NULL,
    "user" uuid NOT NULL,
    platform character varying(50) NOT NULL,
    browser_name character varying(50),
    browser_ver character varying(50),
    user_agent character varying(300),
    token character varying(40) NOT NULL,
    access character varying(50) NOT NULL,
    refresh character varying(40) NOT NULL,
    expires timestamp with time zone,
    created timestamp with time zone DEFAULT now() NOT NULL,
    lastvisit timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE sessions OWNER TO "mlpvc-rr";

--
-- Name: sessions_id_seq; Type: SEQUENCE; Schema: public; Owner: mlpvc-rr
--

CREATE SEQUENCE sessions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE sessions_id_seq OWNER TO "mlpvc-rr";

--
-- Name: sessions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: mlpvc-rr
--

ALTER SEQUENCE sessions_id_seq OWNED BY sessions.id;


--
-- Name: usefullinks; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE usefullinks (
    id integer NOT NULL,
    url character varying(255) NOT NULL,
    label character varying(40) NOT NULL,
    title character varying(255) NOT NULL,
    minrole character varying(10) DEFAULT 'user'::character varying NOT NULL
);


ALTER TABLE usefullinks OWNER TO "mlpvc-rr";

--
-- Name: usefullinks_id_seq; Type: SEQUENCE; Schema: public; Owner: mlpvc-rr
--

CREATE SEQUENCE usefullinks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE usefullinks_id_seq OWNER TO "mlpvc-rr";

--
-- Name: usefullinks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: mlpvc-rr
--

ALTER SEQUENCE usefullinks_id_seq OWNED BY usefullinks.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE TABLE users (
    id uuid NOT NULL,
    name character varying(20) NOT NULL,
    role character varying(10) DEFAULT 'user'::character varying NOT NULL,
    avatar_url character varying(255) NOT NULL,
    signup_date timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE users OWNER TO "mlpvc-rr";

--
-- Name: entryid; Type: DEFAULT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY log ALTER COLUMN entryid SET DEFAULT nextval('log_entryid_seq'::regclass);


--
-- Name: entryid; Type: DEFAULT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY log__banish ALTER COLUMN entryid SET DEFAULT nextval('log__banish_entryid_seq'::regclass);


--
-- Name: entryid; Type: DEFAULT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY log__color_modify ALTER COLUMN entryid SET DEFAULT nextval('log__color_modify_entryid_seq'::regclass);


--
-- Name: entryid; Type: DEFAULT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY log__episodes ALTER COLUMN entryid SET DEFAULT nextval('log__episodes_entryid_seq'::regclass);


--
-- Name: entryid; Type: DEFAULT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY log__img_update ALTER COLUMN entryid SET DEFAULT nextval('log__img_update_entryid_seq'::regclass);


--
-- Name: entryid; Type: DEFAULT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY log__post_lock ALTER COLUMN entryid SET DEFAULT nextval('log__post_lock_entryid_seq'::regclass);


--
-- Name: entryid; Type: DEFAULT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY log__req_delete ALTER COLUMN entryid SET DEFAULT nextval('log__req_delete_entryid_seq'::regclass);


--
-- Name: entryid; Type: DEFAULT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY log__rolechange ALTER COLUMN entryid SET DEFAULT nextval('log__rolechange_entryid_seq'::regclass);


--
-- Name: entryid; Type: DEFAULT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY "log__un-banish" ALTER COLUMN entryid SET DEFAULT nextval('"log__un-banish_entryid_seq"'::regclass);


--
-- Name: entryid; Type: DEFAULT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY log__userfetch ALTER COLUMN entryid SET DEFAULT nextval('log__userfetch_entryid_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY requests ALTER COLUMN id SET DEFAULT nextval('requests_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY reservations ALTER COLUMN id SET DEFAULT nextval('reservations_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY sessions ALTER COLUMN id SET DEFAULT nextval('sessions_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY usefullinks ALTER COLUMN id SET DEFAULT nextval('usefullinks_id_seq'::regclass);


--
-- Name: deviation_cache_provider_id; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY deviation_cache
    ADD CONSTRAINT deviation_cache_provider_id PRIMARY KEY (provider, id);


--
-- Name: episodes__videos_season_episode_provider; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY episodes__videos
    ADD CONSTRAINT episodes__videos_season_episode_provider PRIMARY KEY (season, episode, provider);


--
-- Name: episodes__votes_season_episode_user; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY episodes__votes
    ADD CONSTRAINT episodes__votes_season_episode_user PRIMARY KEY (season, episode, "user");


--
-- Name: episodes_season_episode; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY episodes
    ADD CONSTRAINT episodes_season_episode PRIMARY KEY (season, episode);


--
-- Name: log__banish_entryid; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY log__banish
    ADD CONSTRAINT log__banish_entryid PRIMARY KEY (entryid);


--
-- Name: log__color_modify_entryid; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY log__color_modify
    ADD CONSTRAINT log__color_modify_entryid PRIMARY KEY (entryid);


--
-- Name: log__episode_modify_entryid; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY log__episode_modify
    ADD CONSTRAINT log__episode_modify_entryid PRIMARY KEY (entryid);


--
-- Name: log__episodes_entryid; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY log__episodes
    ADD CONSTRAINT log__episodes_entryid PRIMARY KEY (entryid);


--
-- Name: log__img_update_entryid; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY log__img_update
    ADD CONSTRAINT log__img_update_entryid PRIMARY KEY (entryid);


--
-- Name: log__post_lock_entryid; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY log__post_lock
    ADD CONSTRAINT log__post_lock_entryid PRIMARY KEY (entryid);


--
-- Name: log__req_delete_entryid; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY log__req_delete
    ADD CONSTRAINT log__req_delete_entryid PRIMARY KEY (entryid);


--
-- Name: log__rolechange_entryid; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY log__rolechange
    ADD CONSTRAINT log__rolechange_entryid PRIMARY KEY (entryid);


--
-- Name: log__un-banish_entryid; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY "log__un-banish"
    ADD CONSTRAINT "log__un-banish_entryid" PRIMARY KEY (entryid);


--
-- Name: log__userfetch_entryid; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY log__userfetch
    ADD CONSTRAINT log__userfetch_entryid PRIMARY KEY (entryid);


--
-- Name: log_entryid; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY log
    ADD CONSTRAINT log_entryid PRIMARY KEY (entryid);


--
-- Name: requests_id; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY requests
    ADD CONSTRAINT requests_id PRIMARY KEY (id);


--
-- Name: reservations_id; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY reservations
    ADD CONSTRAINT reservations_id PRIMARY KEY (id);


--
-- Name: roles_name; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY roles
    ADD CONSTRAINT roles_name UNIQUE (name);


--
-- Name: roles_value; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY roles
    ADD CONSTRAINT roles_value PRIMARY KEY (value);


--
-- Name: sessions_id; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY sessions
    ADD CONSTRAINT sessions_id PRIMARY KEY (id);


--
-- Name: usefullinks_id; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY usefullinks
    ADD CONSTRAINT usefullinks_id PRIMARY KEY (id);


--
-- Name: users_id; Type: CONSTRAINT; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT users_id PRIMARY KEY (id);


--
-- Name: episodes__votes_user; Type: INDEX; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE INDEX episodes__votes_user ON episodes__votes USING btree ("user");


--
-- Name: episodes_posted_by; Type: INDEX; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE INDEX episodes_posted_by ON episodes USING btree (posted_by);


--
-- Name: log__banish_target; Type: INDEX; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE INDEX log__banish_target ON log__banish USING btree (target);


--
-- Name: log__rolechange_newrole; Type: INDEX; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE INDEX log__rolechange_newrole ON log__rolechange USING btree (newrole);


--
-- Name: log__rolechange_oldrole; Type: INDEX; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE INDEX log__rolechange_oldrole ON log__rolechange USING btree (oldrole);


--
-- Name: log__rolechange_target; Type: INDEX; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE INDEX log__rolechange_target ON log__rolechange USING btree (target);


--
-- Name: log__un-banish_target; Type: INDEX; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE INDEX "log__un-banish_target" ON "log__un-banish" USING btree (target);


--
-- Name: log__userfetch_userid; Type: INDEX; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE INDEX log__userfetch_userid ON log__userfetch USING btree (userid);


--
-- Name: log_initiator; Type: INDEX; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE INDEX log_initiator ON log USING btree (initiator);


--
-- Name: requests_requested_by; Type: INDEX; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE INDEX requests_requested_by ON requests USING btree (requested_by);


--
-- Name: requests_reserved_by; Type: INDEX; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE INDEX requests_reserved_by ON requests USING btree (reserved_by);


--
-- Name: requests_season_episode; Type: INDEX; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE INDEX requests_season_episode ON requests USING btree (season, episode);


--
-- Name: reservations_reserved_by; Type: INDEX; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE INDEX reservations_reserved_by ON reservations USING btree (reserved_by);


--
-- Name: reservations_season_episode; Type: INDEX; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE INDEX reservations_season_episode ON reservations USING btree (season, episode);


--
-- Name: sessions_user; Type: INDEX; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE INDEX sessions_user ON sessions USING btree ("user");


--
-- Name: usefullinks_minrole; Type: INDEX; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE INDEX usefullinks_minrole ON usefullinks USING btree (minrole);


--
-- Name: users_role; Type: INDEX; Schema: public; Owner: mlpvc-rr; Tablespace: 
--

CREATE INDEX users_role ON users USING btree (role);


--
-- Name: episodes__votes_season_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY episodes__votes
    ADD CONSTRAINT episodes__votes_season_fkey FOREIGN KEY (season, episode) REFERENCES episodes(season, episode) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: episodes__votes_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY episodes__votes
    ADD CONSTRAINT episodes__votes_user_fkey FOREIGN KEY ("user") REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: episodes_posted_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY episodes
    ADD CONSTRAINT episodes_posted_by_fkey FOREIGN KEY (posted_by) REFERENCES users(id) ON UPDATE SET NULL ON DELETE SET NULL;


--
-- Name: log__banish_target_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY log__banish
    ADD CONSTRAINT log__banish_target_fkey FOREIGN KEY (target) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: log__rolechange_newrole_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY log__rolechange
    ADD CONSTRAINT log__rolechange_newrole_fkey FOREIGN KEY (newrole) REFERENCES roles(name) ON UPDATE CASCADE;


--
-- Name: log__rolechange_oldrole_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY log__rolechange
    ADD CONSTRAINT log__rolechange_oldrole_fkey FOREIGN KEY (oldrole) REFERENCES roles(name) ON UPDATE CASCADE;


--
-- Name: log__rolechange_target_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY log__rolechange
    ADD CONSTRAINT log__rolechange_target_fkey FOREIGN KEY (target) REFERENCES users(id) ON UPDATE CASCADE;


--
-- Name: log__un-banish_target_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY "log__un-banish"
    ADD CONSTRAINT "log__un-banish_target_fkey" FOREIGN KEY (target) REFERENCES users(id) ON UPDATE CASCADE;


--
-- Name: log__userfetch_userid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY log__userfetch
    ADD CONSTRAINT log__userfetch_userid_fkey FOREIGN KEY (userid) REFERENCES users(id) ON UPDATE CASCADE;


--
-- Name: log_initiator_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY log
    ADD CONSTRAINT log_initiator_fkey FOREIGN KEY (initiator) REFERENCES users(id) ON UPDATE CASCADE;


--
-- Name: requests_requested_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY requests
    ADD CONSTRAINT requests_requested_by_fkey FOREIGN KEY (requested_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: requests_reserved_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY requests
    ADD CONSTRAINT requests_reserved_by_fkey FOREIGN KEY (reserved_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: requests_season_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY requests
    ADD CONSTRAINT requests_season_fkey FOREIGN KEY (season, episode) REFERENCES episodes(season, episode) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: reservations_reserved_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY reservations
    ADD CONSTRAINT reservations_reserved_by_fkey FOREIGN KEY (reserved_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: reservations_season_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY reservations
    ADD CONSTRAINT reservations_season_fkey FOREIGN KEY (season, episode) REFERENCES episodes(season, episode) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: sessions_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY sessions
    ADD CONSTRAINT sessions_user_fkey FOREIGN KEY ("user") REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: usefullinks_minrole_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY usefullinks
    ADD CONSTRAINT usefullinks_minrole_fkey FOREIGN KEY (minrole) REFERENCES roles(name) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: users_role_fkey; Type: FK CONSTRAINT; Schema: public; Owner: mlpvc-rr
--

ALTER TABLE ONLY users
    ADD CONSTRAINT users_role_fkey FOREIGN KEY (role) REFERENCES roles(name) ON UPDATE SET DEFAULT ON DELETE SET DEFAULT;


--
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;
GRANT USAGE ON SCHEMA public TO "mlpvc-rr";


--
-- Name: deviation_cache; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE deviation_cache FROM PUBLIC;
REVOKE ALL ON TABLE deviation_cache FROM "mlpvc-rr";
GRANT ALL ON TABLE deviation_cache TO "mlpvc-rr";
GRANT ALL ON TABLE deviation_cache TO postgres;


--
-- Name: episodes; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE episodes FROM PUBLIC;
REVOKE ALL ON TABLE episodes FROM "mlpvc-rr";
GRANT ALL ON TABLE episodes TO "mlpvc-rr";
GRANT ALL ON TABLE episodes TO postgres;


--
-- Name: episodes__videos; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE episodes__videos FROM PUBLIC;
REVOKE ALL ON TABLE episodes__videos FROM "mlpvc-rr";
GRANT ALL ON TABLE episodes__videos TO "mlpvc-rr";
GRANT ALL ON TABLE episodes__videos TO postgres;


--
-- Name: episodes__votes; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE episodes__votes FROM PUBLIC;
REVOKE ALL ON TABLE episodes__votes FROM "mlpvc-rr";
GRANT ALL ON TABLE episodes__votes TO "mlpvc-rr";
GRANT ALL ON TABLE episodes__votes TO postgres;


--
-- Name: log; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE log FROM PUBLIC;
REVOKE ALL ON TABLE log FROM "mlpvc-rr";
GRANT ALL ON TABLE log TO "mlpvc-rr";
GRANT ALL ON TABLE log TO postgres;


--
-- Name: log__banish; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE log__banish FROM PUBLIC;
REVOKE ALL ON TABLE log__banish FROM "mlpvc-rr";
GRANT ALL ON TABLE log__banish TO "mlpvc-rr";
GRANT ALL ON TABLE log__banish TO postgres;


--
-- Name: log__banish_entryid_seq; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON SEQUENCE log__banish_entryid_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE log__banish_entryid_seq FROM "mlpvc-rr";
GRANT ALL ON SEQUENCE log__banish_entryid_seq TO "mlpvc-rr";
GRANT ALL ON SEQUENCE log__banish_entryid_seq TO postgres;


--
-- Name: log__color_modify; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE log__color_modify FROM PUBLIC;
REVOKE ALL ON TABLE log__color_modify FROM "mlpvc-rr";
GRANT ALL ON TABLE log__color_modify TO "mlpvc-rr";
GRANT ALL ON TABLE log__color_modify TO postgres;


--
-- Name: log__color_modify_entryid_seq; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON SEQUENCE log__color_modify_entryid_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE log__color_modify_entryid_seq FROM "mlpvc-rr";
GRANT ALL ON SEQUENCE log__color_modify_entryid_seq TO "mlpvc-rr";
GRANT ALL ON SEQUENCE log__color_modify_entryid_seq TO postgres;


--
-- Name: log__episode_modify; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE log__episode_modify FROM PUBLIC;
REVOKE ALL ON TABLE log__episode_modify FROM "mlpvc-rr";
GRANT ALL ON TABLE log__episode_modify TO "mlpvc-rr";
GRANT ALL ON TABLE log__episode_modify TO postgres;


--
-- Name: log__episodes; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE log__episodes FROM PUBLIC;
REVOKE ALL ON TABLE log__episodes FROM "mlpvc-rr";
GRANT ALL ON TABLE log__episodes TO "mlpvc-rr";
GRANT ALL ON TABLE log__episodes TO postgres;


--
-- Name: log__episodes_entryid_seq; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON SEQUENCE log__episodes_entryid_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE log__episodes_entryid_seq FROM "mlpvc-rr";
GRANT ALL ON SEQUENCE log__episodes_entryid_seq TO "mlpvc-rr";
GRANT ALL ON SEQUENCE log__episodes_entryid_seq TO postgres;


--
-- Name: log__img_update; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE log__img_update FROM PUBLIC;
REVOKE ALL ON TABLE log__img_update FROM "mlpvc-rr";
GRANT ALL ON TABLE log__img_update TO "mlpvc-rr";
GRANT ALL ON TABLE log__img_update TO postgres;


--
-- Name: log__img_update_entryid_seq; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON SEQUENCE log__img_update_entryid_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE log__img_update_entryid_seq FROM "mlpvc-rr";
GRANT ALL ON SEQUENCE log__img_update_entryid_seq TO "mlpvc-rr";
GRANT ALL ON SEQUENCE log__img_update_entryid_seq TO postgres;


--
-- Name: log__post_lock; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE log__post_lock FROM PUBLIC;
REVOKE ALL ON TABLE log__post_lock FROM "mlpvc-rr";
GRANT ALL ON TABLE log__post_lock TO "mlpvc-rr";
GRANT ALL ON TABLE log__post_lock TO postgres;


--
-- Name: log__post_lock_entryid_seq; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON SEQUENCE log__post_lock_entryid_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE log__post_lock_entryid_seq FROM "mlpvc-rr";
GRANT ALL ON SEQUENCE log__post_lock_entryid_seq TO "mlpvc-rr";
GRANT ALL ON SEQUENCE log__post_lock_entryid_seq TO postgres;


--
-- Name: log__req_delete; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE log__req_delete FROM PUBLIC;
REVOKE ALL ON TABLE log__req_delete FROM "mlpvc-rr";
GRANT ALL ON TABLE log__req_delete TO "mlpvc-rr";
GRANT ALL ON TABLE log__req_delete TO postgres;


--
-- Name: log__req_delete_entryid_seq; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON SEQUENCE log__req_delete_entryid_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE log__req_delete_entryid_seq FROM "mlpvc-rr";
GRANT ALL ON SEQUENCE log__req_delete_entryid_seq TO "mlpvc-rr";
GRANT ALL ON SEQUENCE log__req_delete_entryid_seq TO postgres;


--
-- Name: log__rolechange; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE log__rolechange FROM PUBLIC;
REVOKE ALL ON TABLE log__rolechange FROM "mlpvc-rr";
GRANT ALL ON TABLE log__rolechange TO "mlpvc-rr";
GRANT ALL ON TABLE log__rolechange TO postgres;


--
-- Name: log__rolechange_entryid_seq; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON SEQUENCE log__rolechange_entryid_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE log__rolechange_entryid_seq FROM "mlpvc-rr";
GRANT ALL ON SEQUENCE log__rolechange_entryid_seq TO "mlpvc-rr";
GRANT ALL ON SEQUENCE log__rolechange_entryid_seq TO postgres;


--
-- Name: log__un-banish; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE "log__un-banish" FROM PUBLIC;
REVOKE ALL ON TABLE "log__un-banish" FROM "mlpvc-rr";
GRANT ALL ON TABLE "log__un-banish" TO "mlpvc-rr";
GRANT ALL ON TABLE "log__un-banish" TO postgres;


--
-- Name: log__un-banish_entryid_seq; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON SEQUENCE "log__un-banish_entryid_seq" FROM PUBLIC;
REVOKE ALL ON SEQUENCE "log__un-banish_entryid_seq" FROM "mlpvc-rr";
GRANT ALL ON SEQUENCE "log__un-banish_entryid_seq" TO "mlpvc-rr";
GRANT ALL ON SEQUENCE "log__un-banish_entryid_seq" TO postgres;


--
-- Name: log__userfetch; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE log__userfetch FROM PUBLIC;
REVOKE ALL ON TABLE log__userfetch FROM "mlpvc-rr";
GRANT ALL ON TABLE log__userfetch TO "mlpvc-rr";
GRANT ALL ON TABLE log__userfetch TO postgres;


--
-- Name: log__userfetch_entryid_seq; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON SEQUENCE log__userfetch_entryid_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE log__userfetch_entryid_seq FROM "mlpvc-rr";
GRANT ALL ON SEQUENCE log__userfetch_entryid_seq TO "mlpvc-rr";
GRANT ALL ON SEQUENCE log__userfetch_entryid_seq TO postgres;


--
-- Name: log_entryid_seq; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON SEQUENCE log_entryid_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE log_entryid_seq FROM "mlpvc-rr";
GRANT ALL ON SEQUENCE log_entryid_seq TO "mlpvc-rr";
GRANT ALL ON SEQUENCE log_entryid_seq TO postgres;


--
-- Name: requests; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE requests FROM PUBLIC;
REVOKE ALL ON TABLE requests FROM "mlpvc-rr";
GRANT ALL ON TABLE requests TO "mlpvc-rr";
GRANT ALL ON TABLE requests TO postgres;


--
-- Name: requests_id_seq; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON SEQUENCE requests_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE requests_id_seq FROM "mlpvc-rr";
GRANT ALL ON SEQUENCE requests_id_seq TO "mlpvc-rr";
GRANT ALL ON SEQUENCE requests_id_seq TO postgres;


--
-- Name: reservations; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE reservations FROM PUBLIC;
REVOKE ALL ON TABLE reservations FROM "mlpvc-rr";
GRANT ALL ON TABLE reservations TO "mlpvc-rr";
GRANT ALL ON TABLE reservations TO postgres;


--
-- Name: reservations_id_seq; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON SEQUENCE reservations_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE reservations_id_seq FROM "mlpvc-rr";
GRANT ALL ON SEQUENCE reservations_id_seq TO "mlpvc-rr";
GRANT ALL ON SEQUENCE reservations_id_seq TO postgres;


--
-- Name: roles; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE roles FROM PUBLIC;
REVOKE ALL ON TABLE roles FROM "mlpvc-rr";
GRANT ALL ON TABLE roles TO "mlpvc-rr";
GRANT ALL ON TABLE roles TO postgres;


--
-- Name: sessions; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE sessions FROM PUBLIC;
REVOKE ALL ON TABLE sessions FROM "mlpvc-rr";
GRANT ALL ON TABLE sessions TO "mlpvc-rr";
GRANT ALL ON TABLE sessions TO postgres;


--
-- Name: sessions_id_seq; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON SEQUENCE sessions_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE sessions_id_seq FROM "mlpvc-rr";
GRANT ALL ON SEQUENCE sessions_id_seq TO "mlpvc-rr";
GRANT ALL ON SEQUENCE sessions_id_seq TO postgres;


--
-- Name: usefullinks; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE usefullinks FROM PUBLIC;
REVOKE ALL ON TABLE usefullinks FROM "mlpvc-rr";
GRANT ALL ON TABLE usefullinks TO "mlpvc-rr";
GRANT ALL ON TABLE usefullinks TO postgres;


--
-- Name: usefullinks_id_seq; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON SEQUENCE usefullinks_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE usefullinks_id_seq FROM "mlpvc-rr";
GRANT ALL ON SEQUENCE usefullinks_id_seq TO "mlpvc-rr";
GRANT ALL ON SEQUENCE usefullinks_id_seq TO postgres;


--
-- Name: users; Type: ACL; Schema: public; Owner: mlpvc-rr
--

REVOKE ALL ON TABLE users FROM PUBLIC;
REVOKE ALL ON TABLE users FROM "mlpvc-rr";
GRANT ALL ON TABLE users TO "mlpvc-rr";
GRANT ALL ON TABLE users TO postgres;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: public; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public REVOKE ALL ON SEQUENCES  FROM PUBLIC;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public REVOKE ALL ON SEQUENCES  FROM postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT SELECT,USAGE ON SEQUENCES  TO "mlpvc-rr";


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: public; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public REVOKE ALL ON TABLES  FROM PUBLIC;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public REVOKE ALL ON TABLES  FROM postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT SELECT ON TABLES  TO "mlpvc-rr";


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: public; Owner: mlpvc-rr
--

ALTER DEFAULT PRIVILEGES FOR ROLE "mlpvc-rr" IN SCHEMA public REVOKE ALL ON TABLES  FROM PUBLIC;
ALTER DEFAULT PRIVILEGES FOR ROLE "mlpvc-rr" IN SCHEMA public REVOKE ALL ON TABLES  FROM "mlpvc-rr";
ALTER DEFAULT PRIVILEGES FOR ROLE "mlpvc-rr" IN SCHEMA public GRANT SELECT,INSERT,DELETE,UPDATE ON TABLES  TO "mlpvc-rr";


--
-- PostgreSQL database dump complete
--

