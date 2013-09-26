-- PostgreSQL schema for PlainIB.
-- /*_*/ indicates the database prefix.

CREATE TABLE /*_*/boards (
    name text PRIMARY KEY CHECK (name <> ''),
    longname text NOT NULL,
    minlevel integer NOT NULL,
    lastid integer NOT NULL
);

CREATE TABLE /*_*/posts (
    globalid serial PRIMARY KEY,
    id integer NOT NULL,
    parent integer NOT NULL,
    board text NOT NULL REFERENCES /*_*/boards(name) ON DELETE CASCADE ON UPDATE CASCADE,
    timestamp timestamp NOT NULL,
    lastbump timestamp NOT NULL,
    ip inet NOT NULL DEFAULT '127.0.0.2',
    name text NOT NULL,
    tripcode text NOT NULL,
    email text NOT NULL,
    subject text NOT NULL,
    comment text NOT NULL,
    password text NOT NULL,
    file text NOT NULL,
    md5 text NOT NULL,
    origname text NOT NULL,
    filesize integer NOT NULL,
    prettysize text NOT NULL,
    width integer NOT NULL,
    height integer NOT NULL,
    thumb text NOT NULL,
    t_width integer NOT NULL,
    t_height integer NOT NULL,
    CHECK (parent = 0 OR parent < id),
    UNIQUE (board, id)
);

CREATE INDEX ON /*_*/posts (id);
CREATE INDEX ON /*_*/posts (parent);
CREATE INDEX ON /*_*/posts (board);
CREATE INDEX ON /*_*/posts (timestamp);
CREATE INDEX ON /*_*/posts (lastbump);

CREATE TABLE /*_*/bans (
    id serial PRIMARY KEY,
    ip cidr UNIQUE NOT NULL,
    timestamp timestamp NOT NULL,
    expire timestamp,
    reason text NOT NULL
);

CREATE INDEX ON /*_*/bans (ip);
CREATE INDEX ON /*_*/bans (expire);

CREATE TABLE /*_*/config (
    name text PRIMARY KEY,
    value text NOT NULL,
    board text REFERENCES /*_*/boards(name) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE (board, name)
);

CREATE INDEX ON /*_*/config (board);

-- FIXME: Now that all posts are stored in the same table, this table only needs
-- to store information for looking up dupes.
CREATE TABLE /*_*/flood (
    id serial PRIMARY KEY,
    ip inet NOT NULL,
    timestamp timestamp NOT NULL,
    imagehash text,
    posthash text,
    isreply boolean NOT NULL
);

CREATE TABLE /*_*/users (
    username text PRIMARY KEY CHECK (username ~ '^\w{1,20}$'),
    password text NOT NULL CHECK (password <> ''),
    hashtype text NOT NULL,
    lastlogin timestamp,
    level integer NOT NULL,
    email text NOT NULL,
    capcode text NOT NULL
);

CREATE TABLE /*_*/spam (
    id serial PRIMARY KEY,
    rules text NOT NULL,
    diff text NOT NULL,
    username TEXT REFERENCES users ON DELETE SET NULL ON UPDATE CASCADE
);


--
-- Post insertion magic
--

-- Increment boards.lastid and set the post's ID to the updated value on INSERT.
CREATE FUNCTION /*_*/insert_post_func() RETURNS trigger AS $$
DECLARE
    updated_row RECORD;
BEGIN
    -- Prevent race conditions
    LOCK TABLE /*_*/boards IN SHARE ROW EXCLUSIVE MODE;

    UPDATE /*_*/boards
        SET lastid = lastid + 1
        WHERE name = NEW.board
        RETURNING lastid
        INTO updated_row;

    NEW.id := updated_row.lastid;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger the above function when we INSERT into the posts row.
CREATE TRIGGER /*_*/insert_post_trigger
    BEFORE INSERT ON /*_*/posts
    FOR EACH ROW EXECUTE PROCEDURE /*_*/insert_post_func();


--
-- Upsert for config table
-- http://stackoverflow.com/questions/1109061/
--

CREATE FUNCTION /*_*/upsert_config(n TEXT, v TEXT, b TEXT) RETURNS VOID AS $$
BEGIN
    LOOP
        IF b IS NULL THEN
            UPDATE /*_*/config SET value = v WHERE name = n AND board IS NULL;
        ELSE
            UPDATE /*_*/config SET value = v WHERE name = n AND board = b;
        END IF;

        IF found THEN
            RETURN;
        END IF;

        -- not there, so try to insert the key
        -- if someone else inserts the same key concurrently,
        -- we could get a unique-key failure
        BEGIN
            INSERT INTO /*_*/config (name, value, board) VALUES (n, v, b);
            RETURN;
        EXCEPTION WHEN unique_violation THEN
            -- do nothing, and loop to try the UPDATE again
        END;
    END LOOP;
END;
$$ LANGUAGE plpgsql;
