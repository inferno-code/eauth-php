CREATE SCHEMA IF NOT EXISTS eauth;

--
-- Table users
--

CREATE TABLE IF NOT EXISTS eauth.users (
  id bigserial NOT NULL,
  email text NOT NULL,
  password text NOT NULL,
  profile json NOT NULL,
  created timestamp with time zone NOT NULL DEFAULT now(),
  locked boolean NOT NULL DEFAULT false,
  invite_id bigint not null,
  CONSTRAINT users_pkey PRIMARY KEY (id)
);

CREATE INDEX users_email_idx
  ON eauth.users
  USING btree
  (email);

CREATE INDEX users_locked_idx
  ON eauth.users
  USING btree
  (locked);

CREATE INDEX users_invite_id_idx
  ON eauth.users
  USING btree
  (invite_id);

--
-- Table invites
--

CREATE TABLE IF NOT EXISTS eauth.invites
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
  ON eauth.invites
  USING btree
  (email);

CREATE INDEX invites_token_idx
  ON eauth.invites
  USING btree
  (token);

CREATE INDEX invites_inviter_user_id_idx
  ON eauth.invites
  USING btree
  (inviter_user_id);

CREATE INDEX invites_expired_idx
  ON eauth.invites
  USING btree
  (expired);

--
-- Table requests_to_recovery_access
--

CREATE TABLE IF NOT EXISTS eauth.requests_to_recovery_access
(
  id bigserial NOT NULL,
  email text NOT NULL,
  token text NOT NULL,
  created timestamp with time zone NOT NULL DEFAULT now(),
  expired timestamp with time zone NOT NULL DEFAULT (now() + '24 hours'::interval),
  activated timestamp with time zone,
  user_id bigint not null,
  CONSTRAINT requests_to_recovery_access_pkey PRIMARY KEY (id),
  CONSTRAINT requests_to_recovery_access_token_uniq UNIQUE (token)
);

CREATE INDEX requests_to_recovery_access_email_idx
  ON eauth.requests_to_recovery_access
  USING btree
  (email);

CREATE INDEX requests_to_recovery_access_token_idx
  ON eauth.requests_to_recovery_access
  USING btree
  (token);

CREATE INDEX requests_to_recovery_access_expired_idx
  ON eauth.requests_to_recovery_access
  USING btree
  (expired);

CREATE INDEX requests_to_recovery_access_user_id_idx
  ON eauth.requests_to_recovery_access
  USING btree
  (user_id);

--
-- Table requests_to_change_email
--

CREATE TABLE IF NOT EXISTS eauth.requests_to_change_email
(
  id bigserial NOT NULL,
  email text NOT NULL,
  token text NOT NULL,
  created timestamp with time zone NOT NULL DEFAULT now(),
  expired timestamp with time zone NOT NULL DEFAULT (now() + '24 hours'::interval),
  activated timestamp with time zone,
  user_id bigint not null,
  CONSTRAINT requests_to_change_email_pkey PRIMARY KEY (id),
  CONSTRAINT requests_to_change_email_token_uniq UNIQUE (token)
);

CREATE INDEX requests_to_change_email_email_idx
  ON eauth.requests_to_change_email
  USING btree
  (email);

CREATE INDEX requests_to_change_email_token_idx
  ON eauth.requests_to_change_email
  USING btree
  (token);

CREATE INDEX requests_to_change_email_expired_idx
  ON eauth.requests_to_change_email
  USING btree
  (expired);

CREATE INDEX requests_to_change_email_user_id_idx
  ON eauth.requests_to_change_email
  USING btree
  (user_id);

--
-- All foreign keys
--

alter table eauth.users
	add CONSTRAINT users_invite_id_fkey FOREIGN KEY (invite_id)
	REFERENCES eauth.invites (id) MATCH SIMPLE
	ON UPDATE CASCADE ON DELETE CASCADE;

alter table eauth.invites
	add CONSTRAINT invites_inviter_user_id_fkey FOREIGN KEY (inviter_user_id)
	REFERENCES eauth.users (id) MATCH SIMPLE
	ON UPDATE CASCADE ON DELETE CASCADE;

alter table eauth.requests_to_recovery_access
	add CONSTRAINT requests_to_recovery_access_user_id_fkey FOREIGN KEY (user_id)
	REFERENCES eauth.users (id) MATCH SIMPLE
	ON UPDATE CASCADE ON DELETE CASCADE;

alter table eauth.requests_to_change_email
	add CONSTRAINT requests_to_change_email_user_id_fkey FOREIGN KEY (user_id)
	REFERENCES eauth.users (id) MATCH SIMPLE
	ON UPDATE CASCADE ON DELETE CASCADE;
