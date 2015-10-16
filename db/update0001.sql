CREATE TABLE sources (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    name,
    feed,
    usereadability INTEGER,
    usecontent     INTEGER,
    enabled        INTEGER
);

CREATE TABLE items (
    id PRIMARY KEY,
    src       INTEGER,
    published INTEGER,
    fetched   INTEGER,
    title,
    url,
    description,
    content
);
