CREATE TABLE "db_user" (
	"id" SERIAL PRIMARY KEY,
	"username" VARCHAR(50) UNIQUE NOT NULL,
	"password" VARCHAR(50) NOT NULL,
	"role" INT NOT NULL,
	"name" VARCHAR(100)
);

SELECT * FROM db_user;

INSERT INTO db_user("username", "password", "role", "name")
VALUES ('admin1', MD5('admin1'), 1, 'Admin 1');

INSERT INTO db_user("username", "password", "role", "name")
VALUES ('user1', MD5('user1'), 0, 'User 1');