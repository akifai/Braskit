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
    name text NOT NULL,
    value text NOT NULL,
    board text REFERENCES /*_*/boards(name) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE (board, name)
);

CREATE INDEX ON /*_*/config (board);

CREATE TABLE /*_*/reports (
    id serial PRIMARY KEY,
    postid integer NOT NULL,
    board text NOT NULL,
    ip inet NOT NULL,
    timestamp timestamp NOT NULL,
    reason text NOT NULL,
    FOREIGN KEY (postid, board) REFERENCES /*_*/posts(id, board) ON DELETE CASCADE ON UPDATE CASCADE
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
    username text REFERENCES /*_*/users ON DELETE SET NULL ON UPDATE CASCADE
);


--
-- Post views
--

-- Adds columns with the boards' configured date formats.
CREATE VIEW /*_*/posts_view AS
    SELECT p.*,
            to_char(
                p.timestamp,
                COALESCE(c.value, 'YY/MM/DD(Dy)HH24:MI')
            ) AS date,
            EXTRACT(EPOCH FROM p.timestamp) AS unixtime
        FROM /*_*/posts AS p
        LEFT OUTER JOIN /*_*/config AS c
            ON (p.board = c.board AND c.name = 'date_format');

-- Same as above, except with reports as JSON and whether or not the IP is
-- banned as a boolean value.
CREATE VIEW /*_*/posts_admin AS
    SELECT p.*,
            to_char(
                p.timestamp,
                COALESCE(c.value, 'YY/MM/DD(Dy)HH24:MI')
            ) AS date,
            EXTRACT(EPOCH FROM p.timestamp) AS unixtime,
            COUNT(b.*) <> 0 AS banned,
            (
                SELECT array_to_json(array_agg(row_to_json(r)))
                    FROM /*_*/reports AS r
                    WHERE p.id = r.postid
                        AND p.board = r.board
            ) as reports
        FROM /*_*/posts AS p
        LEFT OUTER JOIN /*_*/config AS c
            ON (p.board = c.board AND c.name = 'date_format')
        LEFT OUTER JOIN /*_*/bans AS b
            ON (b.ip >>= p.ip)
        GROUP BY p.globalid, c.value;


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
-- Bans view
--

CREATE VIEW /*_*/bans_view AS
    SELECT id, ip,
            -- IP address without the CIDR
            host(ip) AS host,
            -- CIDR
            masklen(ip) AS cidr,
            -- this is an ipv6 address (boolean)
            family(ip) <> 4 AS ipv6,
            -- this is a range ban (boolean)
            CASE WHEN family(ip) <> 4 THEN
                masklen(ip) <> 128
            ELSE
                masklen(ip) <> 32
            END AS range,
            timestamp, expire, reason
        FROM /*_*/bans;


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
