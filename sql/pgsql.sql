CREATE SCHEMA IF NOT EXISTS auth;

--
-- Table users
--

CREATE TABLE IF NOT EXISTS auth.users (
  id bigserial NOT NULL,
  email text NOT NULL,
  password text NOT NULL,
  profile json NOT NULL,
  created timestamp with time zone NOT NULL DEFAULT now(),
  locked boolean NOT NULL DEFAULT true,
  invite_id bigint not null,
  CONSTRAINT users_pkey PRIMARY KEY (id)
);

CREATE INDEX users_email_idx
  ON auth.users
  USING btree
  (email);

CREATE INDEX users_locked_idx
  ON auth.users
  USING btree
  (locked);

CREATE INDEX users_invite_id_idx
  ON auth.users
  USING btree
  (invite_id);

--
-- Table invites
--

CREATE TABLE IF NOT EXISTS auth.invites
(
  id bigserial NOT NULL,
  email text NOT NULL,
  token text NOT NULL,
  created timestamp with time zone NOT NULL DEFAULT now(),
  expired timestamp with time zone NOT NULL DEFAULT (now() + '7 days'::interval),
  activated timestamp with time zone,
  inviter_user_id bigint,
  CONSTRAINT invites_pkey PRIMARY KEY (id),
  CONSTRAINT invites_token_uniq UNIQUE (token)
);

CREATE INDEX invites_email_idx
  ON auth.invites
  USING btree
  (email);

CREATE INDEX invites_token_idx
  ON auth.invites
  USING btree
  (token);

CREATE INDEX invites_inviter_user_id_idx
  ON auth.invites
  USING btree
  (inviter_user_id);

CREATE INDEX invites_expired_idx
  ON auth.invites
  USING btree
  (expired);

--
-- Table recovery_requests
--

CREATE TABLE IF NOT EXISTS auth.recovery_requests
(
  id bigserial NOT NULL,
  email text NOT NULL,
  token text NOT NULL,
  created timestamp with time zone NOT NULL DEFAULT now(),
  expired timestamp with time zone NOT NULL DEFAULT (now() + '24 hours'::interval),
  activated timestamp with time zone,
  user_id bigint not null,
  CONSTRAINT recovery_requests_pkey PRIMARY KEY (id),
  CONSTRAINT recovery_requests_token_uniq UNIQUE (token)
);

CREATE INDEX recovery_requests_email_idx
  ON auth.recovery_requests
  USING btree
  (email);

CREATE INDEX recovery_requests_token_idx
  ON auth.recovery_requests
  USING btree
  (token);

CREATE INDEX recovery_requests_expired_idx
  ON auth.recovery_requests
  USING btree
  (expired);

CREATE INDEX recovery_requests_user_id_idx
  ON auth.recovery_requests
  USING btree
  (user_id);

--
-- Table change_email_requests
--

CREATE TABLE IF NOT EXISTS auth.change_email_requests
(
  id bigserial NOT NULL,
  email text NOT NULL,
  token text NOT NULL,
  created timestamp with time zone NOT NULL DEFAULT now(),
  expired timestamp with time zone NOT NULL DEFAULT (now() + '24 hours'::interval),
  activated timestamp with time zone,
  user_id bigint not null,
  CONSTRAINT change_email_requests_pkey PRIMARY KEY (id),
  CONSTRAINT change_email_requests_token_uniq UNIQUE (token)
);

CREATE INDEX change_email_requests_email_idx
  ON auth.change_email_requests
  USING btree
  (email);

CREATE INDEX change_email_requests_token_idx
  ON auth.change_email_requests
  USING btree
  (token);

CREATE INDEX change_email_requests_expired_idx
  ON auth.change_email_requests
  USING btree
  (expired);

CREATE INDEX change_email_requests_user_id_idx
  ON auth.change_email_requests
  USING btree
  (user_id);

--
-- All foreign keys
--

alter table auth.users
	add CONSTRAINT users_invite_id_fkey FOREIGN KEY (invite_id)
	REFERENCES auth.invites (id) MATCH SIMPLE
	ON UPDATE CASCADE ON DELETE CASCADE;

alter table auth.invites
	add CONSTRAINT invites_inviter_user_id_fkey FOREIGN KEY (inviter_user_id)
	REFERENCES auth.users (id) MATCH SIMPLE
	ON UPDATE CASCADE ON DELETE CASCADE;

alter table auth.recovery_requests
	add CONSTRAINT recovery_requests_user_id_fkey FOREIGN KEY (user_id)
	REFERENCES auth.users (id) MATCH SIMPLE
	ON UPDATE CASCADE ON DELETE CASCADE;

alter table auth.change_email_requests
	add CONSTRAINT change_email_requests_user_id_fkey FOREIGN KEY (user_id)
	REFERENCES auth.users (id) MATCH SIMPLE
	ON UPDATE CASCADE ON DELETE CASCADE;
