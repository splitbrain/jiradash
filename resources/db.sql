CREATE TABLE "sprint" (
  "id" INTEGER NOT NULL PRIMARY KEY,
  "title" TEXT NOT NULL,
  "description" TEXT NOT NULL DEFAULT '',
  "estimate" INTEGER NOT NULL DEFAULT 0,
  "offer" INTEGER NOT NULL DEFAULT 0,
  "created" DATETIME NOT NULL
);

CREATE TABLE "epic" (
  "id" INTEGER NOT NULL PRIMARY KEY,
  "title" TEXT NOT NULL,
  "description" TEXT NOT NULL DEFAULT '',
  "estimate" INTEGER NOT NULL DEFAULT 0,
  "offer" INTEGER NOT NULL DEFAULT 0,
  "created" DATETIME NOT NULL
);

CREATE TABLE "version" (
  "id" INTEGER NOT NULL PRIMARY KEY,
  "title" TEXT NOT NULL,
  "description" TEXT NOT NULL DEFAULT '',
  "estimate" INTEGER NOT NULL DEFAULT 0,
  "offer" INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE "issue" (
  "id" INTEGER NOT NULL PRIMARY KEY,
  "sprint_id" INTEGER NULL,
  "epic_id" INTEGER NULL,
  "version_id" INTEGER NULL,
  "title" TEXT NOT NULL,
  "description" TEXT NOT NULL DEFAULT '',
  "estimate" INTEGER NOT NULL DEFAULT 0,
  "logged" INTEGER NOT NULL DEFAULT 0,
  "type" TEXT NOT NULL DEFAULT '',
  "user" TEXT NOT NULL DEFAULT '',
  "status" TEXT NOT NULL DEFAULT '',
  "created" DATETIME NOT NULL,
  "updated" DATETIME NOT NULL,
  "prio" TEXT NOT NULL DEFAULT 'Normal'
);

CREATE TABLE "worklog" (
  "id" INTEGER NOT NULL PRIMARY KEY,
  "issue_id" INTEGER NOT NULL REFERENCES "issue" ("id"),
  "created" DATETIME NOT NULL,
  "logged" INTEGER NOT NULL DEFAULT 0,
  "user" TEXT NOT NULL,
  "description" TEXT NOT NULL DEFAULT ''
);
