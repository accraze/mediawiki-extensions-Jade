-- Link table between diff judgment pages and the revision they target.
create table /*_*/jade_diff_judgment (
	-- Primary key.  This is for internal use and not guaranteed to be stable.
	jaded_id int unsigned not null primary key auto_increment,
	-- Revision ID being judged.
	jaded_revision int unsigned not null,
	-- Page ID of the judgment.
	jaded_judgment int unsigned not null,
	-- Judged to be damaging?
	jaded_damaging tinyint,
	-- Judged to be good faith?
	jaded_goodfaith tinyint
) /*$wgDBTableOptions*/;

-- Only one judgment per revision.
create unique index /*i*/jaded_revision
	on /*_*/jade_diff_judgment
	(jaded_revision);

-- Covering index, get all data when joining on target revision.
create index /*i*/jaded_covering
	on /*_*/jade_diff_judgment
	(jaded_revision, jaded_judgment, jaded_damaging, jaded_goodfaith);

-- TODO: Review this index once we have an idea of real-world usage statistics.
create index /*i*/jaded_damaging
	on /*_*/jade_diff_judgment
	(jaded_damaging);

-- TODO: Review this index once we have an idea of real-world usage statistics.
create index /*i*/jaded_goodfaith
	on /*_*/jade_diff_judgment
	(jaded_goodfaith);
