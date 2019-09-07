use jol;

alter table contest add homework smallint;

update contest set homework=0;
