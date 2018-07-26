CREATE TABLE IF NOT EXISTS website_token_remember (
  tokenid BIGSERIAL PRIMARY KEY,
  selector TEXT,
  validator TEXT,
  userid BIGINT REFERENCES website_users (userid),
  created TIMESTAMP DEFAULT NOW(),
  modified TIMESTAMP DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS website_token_recovery (
  tokenid BIGSERIAL PRIMARY KEY,
  selector TEXT,
  validator TEXT,
  userid BIGINT REFERENCES website_users (userid),
  created TIMESTAMP DEFAULT NOW(),
  modified TIMESTAMP DEFAULT NOW()
);
