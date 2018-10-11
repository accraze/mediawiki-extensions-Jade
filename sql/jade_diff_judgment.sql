-- Link table between diff judgment pages and the revision they target.
create table /*_*/jade_diff_judgment (
	-- Primary key.  This is for internal use and not guaranteed to be stable.
	jaded_id int unsigned not null primary key auto_increment,
	-- Revision ID being judged.
	jaded_revision int unsigned not null,
	-- Page ID of the judgment.
	jaded_judgment int unsigned not null
) /*$wgDBTableOptions*/;

-- Join judgments by target revision.  Covers judgment page ID.
create unique index /*i*/jaded_revision_judgment
	on /*_*/jade_diff_judgment
	(jaded_revision, jaded_judgment);
