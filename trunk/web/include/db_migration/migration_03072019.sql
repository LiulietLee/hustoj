use jol;

alter table users add reading_authority smallint unsigned;
alter table problem add reading_authority smallint unsigned;

update problem set reading_authority=0;
update users set reading_authority=0;